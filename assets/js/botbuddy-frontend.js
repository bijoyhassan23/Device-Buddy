/* BotBuddy frontend script placeholder */

// document.addEventListener('DOMContentLoaded', function () {

// });

    const root = document.querySelector( '[data-botbuddy-shortcode="botbuddy"]' );
    // if (!root) return;

    const sendMessage = async ( message ) => {
        const response = await fetch( BotBuddyFrontend.apiEndpoint.message, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-BotBuddy-Nonce': BotBuddyFrontend.nonce,
            },
            body: JSON.stringify(message),
        } );

        return response.json();
    };

    // Example usage:
    // sendMessage( 'Hello from the frontend' ).then( console.log ).catch( console.error );

    root.botbuddySendMessage = sendMessage;
    let test = {
        conversation: [
            {
                "role": "user",
                "content": "What is your age?"
            },
            {
                "role": "assistant",
                "content": "I am 26 years old."
            }
        ],
        message: "What I asked previously",
        memory: "User is Bijoy, 26 years old. He previously asked about his age."
    };

    sendMessage(test).then( console.log ).catch( console.error );

    const createMemory = async ( message ) => {
        const response = await fetch( BotBuddyFrontend.apiEndpoint.memory, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-BotBuddy-Nonce': BotBuddyFrontend.nonce,
            },
            body: JSON.stringify(message),
        } );

        return response.json();
    };
    createMemory({
        memory: "User is Bijoy, 26 years old. He previously asked about his age.",
        message: "What I asked previously",
        response: "you asked about your age and I remember that you are 26 years old."
    }).then( console.log ).catch( console.error );
/*
{
    "success": true,
    "message": "Public endpoint structure is ready.",
    memory: "User is Bijoy, 26 years old. He previously asked about his age.",
}
*/