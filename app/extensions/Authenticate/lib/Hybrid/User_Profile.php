<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
 * Hybrid_User_Profile object represents the current logged in user profile. 
 * The list of fields available in the normalized user profile structure used by HybridAuth.  
 *
 * The Hybrid_User_Profile object is populated with as much information about the user as 
 * HybridAuth was able to pull from the given API or authentication provider.
 * 
 * http://hybridauth.sourceforge.net/userguide/Profile_Data_User_Profile.html
 */
class Hybrid_User_Profile
{
	/**
         * The Unique user's ID on the connected provider
         * @var Numeric
         */
        public $identifier = NULL;

	/**
         * User website, blog, web page
         * @var String
         */
        public $webSiteURL = NULL;

	/**
         * URL link to profile page on the IDp web site
         * @var String
         */
        public $profileURL = NULL;

	/**
         * URL link to user photo or avatar
         * @var String
         */
        public $photoURL = NULL;

	/**
         * User displayName provided by the IDp or a concatenation of first and last name.
         * @var String
         */
        public $displayName = NULL;

	/**
         * A short about_me
         * @var String
         */
        public $description = NULL;

	/**
         * User's first name
         * @var String
         */
        public $firstName = NULL;

	/**
         * User's last name
         * @var String
         */
        public $lastName = NULL;

	/**
         * male or female
         * @var String
         */
        public $gender = NULL;

	/**
         * Language
         * @var String
         */
        public $language = NULL;

	/**
         * User age, we don't calculate it. we return it as is if the IDp provide it.
         * @var Numeric
         */
        public $age = NULL;

        /**
         * User birth Day
         * @var Numeric
         */
	public $birthDay = NULL;

        /**
         * User birth Month
         * @var Numeric/String
         */
	public $birthMonth = NULL;

        /**
         * User birth Year
         * @var Numeric
         */
	public $birthYear = NULL;

        /**
         * User email. Note: not all of IDp grant access to the user email
         * @var String
         */
	public $email = NULL;
	
	/**
         * Verified user email. Note: not all of IDp grant access to verified user email
         * @var String
         */
        public $emailVerified = NULL;

	/**
         * Phone number
         * @var String
         */
        public $phone = NULL;

	/**
         * Complete user address
         * @var String
         */
        public $address = NULL;

	/**
         * User country
         * @var String
         */
        public $country = NULL;

	/**
         * Region
         * @var String
         */
        public $region = NULL;

	/**
         * City
         * @var String
         */
        public $city = NULL;

	/**
         * Postal code
         * @var String
         */
        public $zip = NULL;
}
