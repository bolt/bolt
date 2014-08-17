Configuring facebook login
--------------------------

- Log in on facebook with the facebook account you want to use for the site
- Then go to https://developers.facebook.com
- In the "Apps" menu choose "Create a New App"
- You need to enter a "Display name" and a category. You do not need a namespace and leave the test version switch alone.
- Click "Create app" and then fill in the Captcha
- After that your app is created in development mode and you will be redirected to de app dashboard.
- Go to the settings tab and choose "add platform" and choose "Website"
- Then enter your url for site url and mobile site url
- After that add extra subdomains to "App Domains"
- Enter a valid emailaddress in contact email
- Save your settings
- Next go to status & review and set the toggle next to "Do you want to make this app and all its live features available to the general public?" to Yes
- Then go back to the dashboard and copy the App ID and App secret for your config.yml file.


Multiple urls
- In https://developers.facebook.com go to your app then settings and then the advanced tab.
- In security add the url's to the "Valid OAuth redirect URIs"