<?php
/**
 * BotBuddy Admin Page
 */

defined( 'ABSPATH' ) || die( 'Direct access is not allowed' );

if ( ! isset( $settings ) ) {
    $settings = [];
}

if ( ! isset( $settings_updated ) ) {
    $settings_updated = false;
}

$bot_name = isset( $settings['bot_name'] ) ? $settings['bot_name'] : '';
$bot_avatar = isset( $settings['bot_avatar'] ) ? $settings['bot_avatar'] : '';
$doc_id = isset( $settings['doc_id'] ) ? $settings['doc_id'] : '';
$hugging_face_api_key = isset( $settings['hugging_face_api_key'] ) ? $settings['hugging_face_api_key'] : '';
$pinecone_api_key = isset( $settings['pinecone_api_key'] ) ? $settings['pinecone_api_key'] : '';
$pinecone_host = isset( $settings['pinecone_host'] ) ? $settings['pinecone_host'] : '';
$system_prompt = isset( $settings['system_prompt'] ) ? $settings['system_prompt'] : '';
$prompt_template = isset( $settings['prompt_template'] ) ? $settings['prompt_template'] : '';
?>

<div class="wrap botbuddy-admin-page">
    <div class="botbuddy-hero">
        <div>
            <p class="botbuddy-eyebrow">BotBuddy Settings</p>
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p class="botbuddy-summary">
                Edit every chatbot setting from one place, then save the updates with a single button.
            </p>
        </div>
    </div>

    <?php if ( $settings_updated ) : ?>
        <div class="notice notice-success is-dismissible botbuddy-notice">
            <p><?php echo esc_html__( 'BotBuddy settings updated successfully.', 'botbuddy' ); ?></p>
        </div>
    <?php endif; ?>

    <form class="botbuddy-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'botbuddy_save_settings', 'botbuddy_nonce' ); ?>
        <input type="hidden" name="action" value="botbuddy_save_settings" />

        <div class="botbuddy-grid">
            <section class="botbuddy-panel">
                <div class="botbuddy-panel-header">
                    <div>
                        <span class="botbuddy-panel-tag">Edit</span>
                        <h2>Bot Identity</h2>
                    </div>
                </div>

                <label class="botbuddy-field">
                    <span>Bot Name</span>
                    <input type="text" name="botbuddy_settings[bot_name]" value="<?php echo esc_attr( $bot_name ); ?>" placeholder="BotBuddy" />
                </label>

                <label class="botbuddy-field">
                    <span>Bot Avatar URL</span>
                    <input type="url" name="botbuddy_settings[bot_avatar]" value="<?php echo esc_attr( $bot_avatar ); ?>" placeholder="https://example.com/avatar.png" />
                </label>

                <label class="botbuddy-field">
                    <span>Document / Knowledge Base ID</span>
                    <input type="text" name="botbuddy_settings[doc_id]" value="<?php echo esc_attr( $doc_id ); ?>" placeholder="Enter your document ID" />
                </label>

                <div class="botbuddy-avatar-preview">
                    <span>Avatar Preview</span>
                    <?php if ( ! empty( $bot_avatar ) ) : ?>
                        <img src="<?php echo esc_url( $bot_avatar ); ?>" alt="<?php echo esc_attr( $bot_name ); ?>" />
                    <?php else : ?>
                        <div class="botbuddy-avatar-placeholder">No avatar configured</div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="botbuddy-panel">
                <div class="botbuddy-panel-header">
                    <div>
                        <span class="botbuddy-panel-tag">Edit</span>
                        <h2>API Keys</h2>
                    </div>
                </div>

                <label class="botbuddy-field">
                    <span>Hugging Face API Key</span>
                    <input type="text" name="botbuddy_settings[hugging_face_api_key]" value="<?php echo esc_attr( $hugging_face_api_key ); ?>" placeholder="hf_..." autocomplete="off" />
                </label>

                <label class="botbuddy-field">
                    <span>Pinecone API Key</span>
                    <input type="text" name="botbuddy_settings[pinecone_api_key]" value="<?php echo esc_attr( $pinecone_api_key ); ?>" placeholder="pinecone_..." autocomplete="off" />
                </label>

                <label class="botbuddy-field">
                    <span>Pinecone Host</span>
                    <input type="url" name="botbuddy_settings[pinecone_host]" value="<?php echo esc_attr( $pinecone_host ); ?>" placeholder="https://controller.us-east-1.pinecone.io" />
                </label>

                <div class="botbuddy-help-box">
                    <strong>Update option</strong>
                    <p>Enter new values in any field and click the update button below to save them.</p>
                </div>
            </section>

            <section class="botbuddy-panel botbuddy-panel-wide">
                <div class="botbuddy-panel-header">
                    <div>
                        <span class="botbuddy-panel-tag">Edit</span>
                        <h2>Prompt Settings</h2>
                    </div>
                </div>

                <label class="botbuddy-field">
                    <span>System Prompt</span>
                    <textarea name="botbuddy_settings[system_prompt]" rows="6" placeholder="Write the bot behavior instructions here."><?php echo esc_textarea( $system_prompt ); ?></textarea>
                </label>

                <label class="botbuddy-field">
                    <span>Prompt Template</span>
                    <textarea name="botbuddy_settings[prompt_template]" rows="10" placeholder="Use %s placeholders for context and question."><?php echo esc_textarea( $prompt_template ); ?></textarea>
                </label>

                <div class="botbuddy-template-hint">
                    <strong>Template placeholders</strong>
                    <code>%s</code> for context and <code>%s</code> for the user question.
                </div>
            </section>
        </div>

        <div class="botbuddy-actions">
            <button type="submit" class="button button-primary button-large">Update Settings</button>
            <p class="botbuddy-actions-note">All changes are saved to the plugin options immediately after update.</p>
        </div>
    </form>
</div>
