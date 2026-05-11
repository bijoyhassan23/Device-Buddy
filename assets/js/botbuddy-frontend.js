

    const root = document.querySelector( '[data-botbuddy-shortcode="botbuddy"]' );

    // Ensure the API endpoint and nonce are available
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
    let message = {
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

    sendMessage(message).then( console.log ).catch( console.error );
    /*
    response from the above message will be like this:
    [
        'success' => true,
        'message' => 'Public endpoint structure is ready.',
        'received' => $get_response,
    ]
     */

    // Memory API test
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
    let memoerytest = {
        memory: "User is Bijoy, 26 years old. He previously asked about his age.",
        message: "What I asked previously",
        response: "you asked about your age and I remember that you are 26 years old."
    };
    createMemory(memoerytest).then( console.log ).catch( console.error );

    /*
    response from the above memory test will be like this:
    [
        'success' => true,
        'message' => 'Memory request route is ready.',
        'received' => $get_response,
    ]
    */