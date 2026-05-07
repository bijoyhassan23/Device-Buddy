import { RecursiveCharacterTextSplitter } from "https://cdn.jsdelivr.net/npm/@langchain/textsplitters@1.0.1/+esm";
document.addEventListener('DOMContentLoaded', function () {
    const settingsForm = document.querySelector('.botbuddy-settings-form');
    const updateSettingsButton = document.getElementById('botbuddy-update-settings');
    let savingSettings = false;

    if (settingsForm) {
        settingsForm.addEventListener('submit', saveSettingsViaAjax);
    }

    let chunkingButton = document.getElementById('botbuddy-create-chunking');
    if (!chunkingButton) {
        return;
    }

    chunkingButton.addEventListener('click', runChunking());

    function runChunking() {
        let calling = false;
        return async function () {
            if (calling) return;

            calling = true;
            chunkingButton.disabled = true;
            chunkingButton.textContent = 'Chunking in progress...';

            try{
                const response = await fetch(`https://docs.google.com/document/d/${BotBuddyAdmin.settings.doc_id}/export?format=txt`);
                if (!response.ok) {
                    throw new Error(`Failed to fetch document: ${response.statusText}`);
                }
                const text = await response.text();

                // Initialize the text splitter
                const splitter = new RecursiveCharacterTextSplitter({
                    chunkSize: 800,
                    chunkOverlap: 150,
                    separators: ["\n\n", "\n", ".", "!", "?", " ", ""]
                });

                // Split the text into chunks
                let chunks = await splitter.createDocuments([text]);
                chunks = chunks.map((chunk, index) => ({ id: `chunk-${index + 1}`, metadata: { text: chunk.pageContent } }));
                
                let chunkingOptions = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': BotBuddyAdmin.restApi.nonce
                    },
                    body: JSON.stringify(chunks)
                };
                let chunkingResponse = await fetch(BotBuddyAdmin.restApi.chunkingUrl, chunkingOptions);
                let temp = await chunkingResponse.json();
                showToast(temp?.data?.message || '', 'success');
                updateLogOutput(temp?.data?.logs || '');
                console.log( temp);
            } catch (error) {
                console.error('Error during chunking:', error);
                showToast('An error occurred while running chunking. Please check the console for details.', 'error');
            } finally {
                calling = false;
                chunkingButton.disabled = false;
                chunkingButton.textContent = 'Run Chunking';
            }
        };
    }

    async function saveSettingsViaAjax(event) {
        event.preventDefault();

        if (savingSettings) {
            return;
        }
        savingSettings = true;

        const formData = new FormData(settingsForm);
        formData.set('action', BotBuddyAdmin.ajax.saveAction);

        setUpdateButtonLoading(true);

        try {
            const response = await fetch(BotBuddyAdmin.ajax.url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                const errorMessage = payload?.data?.message || 'Unable to update settings.';
                throw new Error(errorMessage);
            }

            showToast(payload.data.message || 'Settings updated successfully.', 'success');
            updateLogOutput(payload?.data?.logs || '');
        } catch (error) {
            showToast(error.message || 'An unexpected error occurred.', 'error');
        } finally {
            setUpdateButtonLoading(false);
            savingSettings = false;
        }
    }

    function setUpdateButtonLoading(isLoading) {
        if (!updateSettingsButton) {
            return;
        }

        updateSettingsButton.disabled = isLoading;
        updateSettingsButton.textContent = isLoading ? 'Updating...' : 'Update Settings';
        updateSettingsButton.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    }

    function showToast(message, type) {
        const container = getToastContainer();
        if (!container) {
            return;
        }

        const toast = document.createElement('div');
        toast.className = `botbuddy-toast botbuddy-toast--${type}`;
        toast.textContent = message;
        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.add('is-visible');
        });

        window.setTimeout(() => {
            toast.classList.remove('is-visible');
            window.setTimeout(() => toast.remove(), 220);
        }, 3200);
    }

    function getToastContainer() {
        let container = document.querySelector('.botbuddy-toast-container');
        if (container) {
            return container;
        }

        const page = document.querySelector('.botbuddy-admin-page');
        if (!page) {
            return null;
        }

        container = document.createElement('div');
        container.className = 'botbuddy-toast-container';
        page.appendChild(container);
        return container;
    }

    function updateLogOutput(logText) {
        const logBody = document.querySelector('.botbuddy-log-card-body');
        if (!logBody) {
            return;
        }

        logBody.innerHTML = '';

        if (logText && logText.trim() !== '') {
            const pre = document.createElement('pre');
            pre.className = 'botbuddy-log-output';
            pre.textContent = logText;
            logBody.appendChild(pre);
            return;
        }

        const emptyState = document.createElement('div');
        emptyState.className = 'botbuddy-log-empty';
        emptyState.textContent = 'No logs yet. Settings saves and other log events will appear here.';
        logBody.appendChild(emptyState);
    }
});
