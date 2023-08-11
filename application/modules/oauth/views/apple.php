<html>
    <head>
    </head>
    <body>
        <script type="text/javascript" src="https://appleid.cdn-apple.com/appleauth/static/jsapi/appleid/1/en_US/appleid.auth.js"></script>
        <div id="appleid-signin" data-color="black" data-border="true" data-type="sign in"></div>
        <script type="text/javascript">
            AppleID.auth.init({
                clientId : 'id.botfood.signinservice',
                scope : 'email name',
                redirectURI : 'https://api.1itmedia.co.id/oauth/google',
                state : 'EN',
                usePopup : true
            });

            document.addEventListener('AppleIDSignInOnSuccess', (event) => {
                // Handle successful response.
                console.log(event.detail);
            });


            // Listen for authorization failures.
            document.addEventListener('AppleIDSignInOnFailure', (event) => {
                // Handle error.
                console.log(event.detail);
            });
        </script>
    </body>
</html>