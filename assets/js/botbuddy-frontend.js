
(function() {
    'use strict';

    // =====================================================
    // 1. INDEXEDDB MANAGEMENT
    // =====================================================
    
    const DB_NAME = 'BotBuddyDB';
    const DB_VERSION = 1;
    const STORE_NAME = 'messages';
    
    let dbInstance = null;

    /**
     * Initialize IndexedDB database
     */
    async function initializeDatabase() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                dbInstance = request.result;
                resolve(dbInstance);
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    const objectStore = db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                    objectStore.createIndex('created_at', 'created_at', { unique: false });
                }
            };
        });
    }

    /**
     * Save a message to IndexedDB
     */
    async function saveMessage(messageData) {
        if (!dbInstance) await initializeDatabase();
        
        return new Promise((resolve, reject) => {
            const transaction = dbInstance.transaction([STORE_NAME], 'readwrite');
            const objectStore = transaction.objectStore(STORE_NAME);
            const message = {
                role: messageData.role,
                message: messageData.message,
                created_at: new Date().toISOString(),
            };
            
            const request = objectStore.add(message);
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(request.result);
        });
    }

    /**
     * Get all messages from IndexedDB
     */
    async function getMessages() {
        if (!dbInstance) await initializeDatabase();
        
        return new Promise((resolve, reject) => {
            const transaction = dbInstance.transaction([STORE_NAME], 'readonly');
            const objectStore = transaction.objectStore(STORE_NAME);
            const request = objectStore.getAll();
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(request.result || []);
        });
    }

    /**
     * Get last 5 messages from IndexedDB for context
     */
    async function getLastFiveMessages() {
        const allMessages = await getMessages();
        return allMessages.slice(-5);
    }

    /**
     * Delete a specific message from IndexedDB
     */
    async function deleteMessage(messageId) {
        if (!dbInstance) await initializeDatabase();
        
        return new Promise((resolve, reject) => {
            const transaction = dbInstance.transaction([STORE_NAME], 'readwrite');
            const objectStore = transaction.objectStore(STORE_NAME);
            const request = objectStore.delete(messageId);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve();
        });
    }

    /**
     * Clear all messages from IndexedDB
     */
    async function clearMessages() {
        if (!dbInstance) await initializeDatabase();
        
        return new Promise((resolve, reject) => {
            const transaction = dbInstance.transaction([STORE_NAME], 'readwrite');
            const objectStore = transaction.objectStore(STORE_NAME);
            const request = objectStore.clear();
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve();
        });
    }

    // =====================================================
    // 2. API COMMUNICATION (Keep existing structure)
    // =====================================================

    const sendMessage = async (message) => {
        const response = await fetch(BotBuddyFrontend.apiEndpoint.message, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-BotBuddy-Nonce': BotBuddyFrontend.nonce,
            },
            body: JSON.stringify(message),
        });

        return response.json();
    };

    const createMemory = async (memoryData) => {
        const response = await fetch(BotBuddyFrontend.apiEndpoint.memory, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-BotBuddy-Nonce': BotBuddyFrontend.nonce,
            },
            body: JSON.stringify(memoryData),
        });

        return response.json();
    };

    // =====================================================
    // 3. UI RENDERING
    // =====================================================

    const root = document.querySelector('[data-botbuddy-shortcode="botbuddy"]');
    const botName = (window.BotBuddyFrontend && BotBuddyFrontend.botName) ? BotBuddyFrontend.botName : 'BotBuddy';
    const botImageUrl = (window.BotBuddyFrontend && BotBuddyFrontend.botImageUrl) ? BotBuddyFrontend.botImageUrl : '';

    function normalizeAssistantMessage(message) {
        if (typeof message !== 'string') {
            return '';
        }

        const trimmedMessage = message.trim();
        const hasWrappingDoubleQuotes = trimmedMessage.length > 1 && trimmedMessage.startsWith('"') && trimmedMessage.endsWith('"');
        const hasWrappingSingleQuotes = trimmedMessage.length > 1 && trimmedMessage.startsWith("'") && trimmedMessage.endsWith("'");

        if (hasWrappingDoubleQuotes || hasWrappingSingleQuotes) {
            return trimmedMessage.slice(1, -1).trim();
        }

        return trimmedMessage;
    }

    function setChatIdentity() {
        const headerTitle = document.querySelector('.botbuddy-header__title');
        const headerSubtitle = document.querySelector('.botbuddy-header__subtitle');

        if (headerTitle) {
            headerTitle.textContent = botName;
        }

        if (headerSubtitle) {
            headerSubtitle.textContent = 'Ask me anything';
        }
    }
    
    /**
     * Render a message bubble
     */
    function renderMessage(messageObj) {
        const messagesArea = document.querySelector('.botbuddy-messages');
        if (!messagesArea) return;

        const messageEl = document.createElement('div');
        messageEl.className = `botbuddy-message botbuddy-message--${messageObj.role}`;
        messageEl.dataset.messageId = messageObj.id;

        const contentEl = document.createElement('div');
        contentEl.className = 'botbuddy-message__content';
        contentEl.textContent = messageObj.role === 'assistant' ? normalizeAssistantMessage(messageObj.message) : messageObj.message;

        if (messageObj.role === 'assistant') {
            const avatarEl = document.createElement('div');
            avatarEl.className = 'botbuddy-message__avatar';

            if (botImageUrl) {
                const avatarImg = document.createElement('img');
                avatarImg.className = 'botbuddy-message__avatar-image';
                avatarImg.src = botImageUrl;
                avatarImg.alt = botName;
                avatarImg.loading = 'lazy';
                avatarEl.appendChild(avatarImg);
            } else {
                avatarEl.textContent = botName.charAt(0).toUpperCase();
            }

            const bodyEl = document.createElement('div');
            bodyEl.className = 'botbuddy-message__body';

            const metaEl = document.createElement('div');
            metaEl.className = 'botbuddy-message__meta';
            metaEl.textContent = botName;

            bodyEl.appendChild(metaEl);
            bodyEl.appendChild(contentEl);

            messageEl.appendChild(avatarEl);
            messageEl.appendChild(bodyEl);
        } else {
            messageEl.appendChild(contentEl);
        }

        messagesArea.appendChild(messageEl);

        scrollToBottom();
    }

    /**
     * Render typing indicator
     */
    function showTypingIndicator() {
        const messagesArea = document.querySelector('.botbuddy-messages');
        if (!messagesArea) return;

        const typingEl = document.createElement('div');
        typingEl.className = 'botbuddy-message botbuddy-message--assistant botbuddy-typing-indicator';
        typingEl.id = 'botbuddy-typing';

        typingEl.innerHTML = `
            <div class="botbuddy-message__avatar botbuddy-message__avatar--placeholder"></div>
            <div class="botbuddy-message__body">
                <div class="botbuddy-message__meta">${botName}</div>
                <div class="botbuddy-message__content botbuddy-message__content--typing">
                    <span class="botbuddy-typing__dot"></span>
                    <span class="botbuddy-typing__dot"></span>
                    <span class="botbuddy-typing__dot"></span>
                </div>
            </div>
        `;

        messagesArea.appendChild(typingEl);
        scrollToBottom();
    }

    /**
     * Remove typing indicator
     */
    function hideTypingIndicator() {
        const typingEl = document.getElementById('botbuddy-typing');
        if (typingEl) {
            typingEl.remove();
        }
    }

    /**
     * Auto-scroll to bottom of messages
     */
    function scrollToBottom() {
        const messagesArea = document.querySelector('.botbuddy-messages');
        if (messagesArea) {
            setTimeout(() => {
                messagesArea.scrollTop = messagesArea.scrollHeight;
            }, 0);
        }
    }

    /**
     * Clear message input field
     */
    function clearInput() {
        const input = document.querySelector('.botbuddy-input');
        if (input) {
            input.value = '';
        }
    }

    /**
     * Disable/Enable send button
     */
    function setSendButtonLoading(isLoading) {
        const button = document.querySelector('.botbuddy-send-btn');
        if (button) {
            button.disabled = isLoading;
            button.classList.toggle('botbuddy-send-btn--loading', isLoading);
        }
    }

    /**
     * Load and render all messages from IndexedDB
     */
    async function loadPreviousMessages() {
        const allMessages = await getMessages();
        allMessages.forEach(msg => {
            renderMessage(msg);
        });
    }

    /**
     * Show empty state if no messages
     */
    function updateEmptyState() {
        const messagesArea = document.querySelector('.botbuddy-messages');
        const allMessages = messagesArea?.querySelectorAll('.botbuddy-message:not(.botbuddy-typing-indicator)');
        const emptyState = document.querySelector('.botbuddy-empty-state');

        if (emptyState) {
            emptyState.style.display = allMessages && allMessages.length > 0 ? 'none' : 'flex';
        }
    }

    // =====================================================
    // 4. MESSAGE HANDLING
    // =====================================================

    /**
     * Build context from last 5 messages
     */
    async function buildContext() {
        const lastFiveMessages = await getLastFiveMessages();
        const conversation = lastFiveMessages.map(msg => ({
            role: msg.role,
            content: msg.message,
        }));

        const memory = lastFiveMessages.map(msg => 
            `${msg.role === 'user' ? 'User' : 'Assistant'}: ${msg.message}`
        ).join('\n');

        return { conversation, memory };
    }

    /**
     * Send message to API and handle response
     */
    async function handleSendMessage(userMessageText) {
        if (!userMessageText.trim()) return;

        setSendButtonLoading(true);
        clearInput();

        try {
            // Save user message to IndexedDB
            await saveMessage({
                role: 'user',
                message: userMessageText,
            });

            // Render user message
            const userMsg = {
                id: Date.now(),
                role: 'user',
                message: userMessageText,
            };
            renderMessage(userMsg);
            updateEmptyState();

            // Build context from previous messages
            const { conversation, memory } = await buildContext();

            // Show typing indicator
            showTypingIndicator();

            // Send message to API
            const apiPayload = {
                conversation,
                message: userMessageText,
                memory: memory || '',
            };

            // const apiResponse = await sendMessage(apiPayload);
            // Streamed path (uses server-side streaming endpoint)
            await handleStreamSendMessage(apiPayload);
            // When using streaming, the assistant message is handled by the stream handler.
            // Return early to avoid running the non-streaming response handling below.
            return;

            // Hide typing indicator
            hideTypingIndicator();

            // Handle assistant response
            let assistantMessage = 'I encountered an error processing your message.';
            
            if (apiResponse && typeof apiResponse === 'string') {
                assistantMessage = normalizeAssistantMessage(apiResponse);
            } else if (apiResponse?.body?.choices?.[0]?.message?.content) {
                assistantMessage = normalizeAssistantMessage(apiResponse.body.choices[0].message.content);
            } else if (apiResponse?.received) {
                assistantMessage = normalizeAssistantMessage(JSON.stringify(apiResponse.received));
            }

            // Save assistant message to IndexedDB
            await saveMessage({
                role: 'assistant',
                message: assistantMessage,
            });

            // Render assistant message
            const assistantMsg = {
                id: Date.now() + 1,
                role: 'assistant',
                message: assistantMessage,
            };
            renderMessage(assistantMsg);

            // Create memory record (optional background task)
            createMemory({
                memory: memory || '',
                message: userMessageText,
                response: assistantMessage,
            }).catch(err => console.warn('Memory API error:', err));

        } catch (error) {
            console.error('Error sending message:', error);
            hideTypingIndicator();

            const errorMsg = {
                id: Date.now(),
                role: 'assistant',
                message: 'Sorry, I could not process your message. Please try again.',
            };
            renderMessage(errorMsg);
        } finally {
            setSendButtonLoading(false);
        }
    }

    /**
     * Stream-enabled send message flow (POST -> SSE / stream reader)
     * NOTE: This function is added as a streaming alternative but is not
     * automatically called until you wire it in (the original call was
     * intentionally commented out above).
     */
    async function handleStreamSendMessage(apiPayload) {
        const streamEndpoint = (BotBuddyFrontend.apiEndpoint.message || '').replace('/message', '/message_stream');
        if (!streamEndpoint) {
            throw new Error('Streaming endpoint not configured');
        }

        // Remove generic typing indicator (we'll render a single assistant placeholder)
        hideTypingIndicator();

        // Create assistant placeholder
        const messagesArea = document.querySelector('.botbuddy-messages');
        if (!messagesArea) return;

        const assistantEl = document.createElement('div');
        assistantEl.className = 'botbuddy-message botbuddy-message--assistant';
        assistantEl.dataset.streaming = '1';

        const avatarEl = document.createElement('div');
        avatarEl.className = 'botbuddy-message__avatar';
        if (botImageUrl) {
            const img = document.createElement('img'); img.className = 'botbuddy-message__avatar-image'; img.src = botImageUrl; img.alt = botName; img.loading = 'lazy'; avatarEl.appendChild(img);
        } else { avatarEl.textContent = botName.charAt(0).toUpperCase(); }

        const bodyEl = document.createElement('div');
        bodyEl.className = 'botbuddy-message__body';
        const metaEl = document.createElement('div'); metaEl.className = 'botbuddy-message__meta'; metaEl.textContent = botName;
        const contentEl = document.createElement('div'); contentEl.className = 'botbuddy-message__content';

        // Stream loader shown inside the message content until the first token arrives
        const loaderEl = document.createElement('div');
        loaderEl.className = 'botbuddy-stream-loader';
        loaderEl.innerHTML = '<span class="botbuddy-typing__dot"></span><span class="botbuddy-typing__dot"></span><span class="botbuddy-typing__dot"></span>';
        contentEl.appendChild(loaderEl);

        bodyEl.appendChild(metaEl);
        bodyEl.appendChild(contentEl);
        assistantEl.appendChild(avatarEl);
        assistantEl.appendChild(bodyEl);

        messagesArea.appendChild(assistantEl);
        scrollToBottom();

        let response;
        try {
            response = await fetch(streamEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-BotBuddy-Nonce': BotBuddyFrontend.nonce,
                },
                body: JSON.stringify(apiPayload),
            });
        } catch (err) {
            console.error('Streaming request failed:', err);
            contentEl.textContent = 'Error starting streaming response.';
            hideTypingIndicator();
            return;
        }

        if (!response.body) {
            const text = await response.text();
            contentEl.textContent = text || 'No streaming body.';
            hideTypingIndicator();
            return;
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let finalText = '';
        let streamStarted = false;

        // Read loop
        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            buffer += decoder.decode(value, { stream: true });

            // SSE frames are separated by double newlines
            let parts = buffer.split('\n\n');
            buffer = parts.pop(); // remainder

            for (const part of parts) {
                const lines = part.split('\n').map(l => l.trim());
                for (const line of lines) {
                    if (!line) continue;
                    if (line.startsWith('data:')) {
                        const payloadText = line.replace(/^data:\s*/, '');
                        if (payloadText === '[DONE]') continue;
                        let parsed = null;
                        try { parsed = JSON.parse(payloadText); } catch (e) { parsed = null; }
                        if (parsed && parsed.token) {
                            if (!streamStarted) {
                                streamStarted = true;
                                if (loaderEl && loaderEl.parentNode) loaderEl.parentNode.removeChild(loaderEl);
                            }

                            finalText += parsed.token;
                            contentEl.textContent = finalText;
                            scrollToBottom();
                        } else if (parsed && parsed.text) {
                            if (!streamStarted) {
                                streamStarted = true;
                                if (loaderEl && loaderEl.parentNode) loaderEl.parentNode.removeChild(loaderEl);
                            }

                            finalText = parsed.text;
                            contentEl.textContent = finalText;
                            scrollToBottom();
                        }
                    }
                }
            }
        }

        // finalize
        if (finalText) {
            // save assistant message
            await saveMessage({ role: 'assistant', message: finalText });
        }

        // remove typing indicator if present
        hideTypingIndicator();

        // ensure UI reflects final content; remove loader if it never started
        if (!streamStarted) {
            if (loaderEl && loaderEl.parentNode) loaderEl.parentNode.removeChild(loaderEl);
        }

        contentEl.textContent = finalText || contentEl.textContent;
        scrollToBottom();
    }

    // =====================================================
    // 5. EVENT LISTENERS
    // =====================================================

    /**
     * Initialize event listeners
     */
    function initializeEventListeners() {
        const input = document.querySelector('.botbuddy-input');
        const sendBtn = document.querySelector('.botbuddy-send-btn');
        const clearBtn = document.querySelector('.botbuddy-clear-btn');

        if (input && sendBtn) {
            // Send on button click
            sendBtn.addEventListener('click', () => {
                const message = input.value.trim();
                if (message) {
                    handleSendMessage(message);
                }
            });

            // Send on Enter key
            input.addEventListener('keypress', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    const message = input.value.trim();
                    if (message) {
                        handleSendMessage(message);
                    }
                }
            });
        }

        // Clear chat history
        if (clearBtn) {
            clearBtn.addEventListener('click', async () => {
                if (confirm('Are you sure you want to clear chat history?')) {
                    await clearMessages();
                    const messagesArea = document.querySelector('.botbuddy-messages');
                    if (messagesArea) {
                        messagesArea.innerHTML = '';
                    }
                    updateEmptyState();
                }
            });
        }
    }

    // =====================================================
    // 6. INITIALIZATION
    // =====================================================

    /**
     * Initialize the chatbot on DOM ready
     */
    async function initializeBot() {
        try {
            // Ensure database is ready
            await initializeDatabase();

            setChatIdentity();

            // Load previous messages
            await loadPreviousMessages();
            updateEmptyState();

            // Setup event listeners
            initializeEventListeners();

            // Make functions accessible globally for debugging
            root.botbuddySendMessage = sendMessage;
            root.botbuddyCreateMemory = createMemory;
            root.botbuddy = {
                getMessages,
                getLastFiveMessages,
                clearMessages,
                saveMessage,
            };

            console.log('BotBuddy initialized successfully');
        } catch (error) {
            console.error('Error initializing BotBuddy:', error);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeBot);
    } else {
        initializeBot();
    }
})();