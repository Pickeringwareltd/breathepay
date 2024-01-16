## Things to note

There are 2 config variables with IDs for 3DS and none 3DS accounts to test both flows

When installing the package, we must ensure:

- The correct routes are added (could be optional to make it more flexible)
- The routes are then added to the VerifyCsrfToken middleware
- The breathepay charges table is added to the database
- The relevant merchant ID and secret is loaded in when making payments (I'd avoid adding this to config like I have as SaaS will have a merchant ID + Secret per business)
- Then they will need to add the JS package to the frontend (ideally we can make our own NPM one which pulls in their package)
