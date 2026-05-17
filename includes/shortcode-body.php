<?php
/**
 * BotBuddy shortcode body template.
 * 
 * This file outputs the HTML structure for the chatbot.
 * CSS and JavaScript are already enqueued by the shortcode class.
 */

defined( 'ABSPATH' ) or die( 'Direct access is not allowed' );
?>

<div class="botbuddy-container" data-botbuddy-shortcode="botbuddy">
    <div class="botbuddy-chat">
        <!-- Chat Header -->
        <div class="botbuddy-header">
            <div class="botbuddy-header__content">
                <h3 class="botbuddy-header__title">BotBuddy</h3>
                <p class="botbuddy-header__subtitle">Ask me anything</p>
            </div>
            <button class="botbuddy-header__close" aria-label="Close chat">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Messages Area -->
        <div class="botbuddy-messages">
            <!-- Empty State -->
            <div class="botbuddy-empty-state">
                <div class="botbuddy-empty-state__icon">
                    <img src="<?php echo $this->settings["bot_avatar"]; ?>" alt="Bot Avatar">
                </div>
                <h3 class="botbuddy-empty-state__title">Start a conversation</h3>
                <p class="botbuddy-empty-state__text">Ask me anything and I'll help you find the answer</p>
            </div>

            <!-- Messages will be rendered here -->
        </div>

        <!-- Input Area -->
        <div class="botbuddy-input-area">
            <div class="botbuddy-input-wrapper">
                <input 
                    type="text" 
                    class="botbuddy-input" 
                    placeholder="Type your message..." 
                    aria-label="Message input"
                >
                <button class="botbuddy-send-btn" aria-label="Send message">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
            <button class="botbuddy-clear-btn" title="Clear chat history">
                Start New Conversation
                <!-- <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg> -->
            </button>
        </div>
    </div>
</div>

