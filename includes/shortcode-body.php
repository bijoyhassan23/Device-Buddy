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
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"></path>
                    </svg>
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
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
            </button>
        </div>
    </div>
</div>

