<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\LoginLdap\LdapInterop;

use Exception;
use Piwik\Plugins\LoginLdap\Config;

/**
 * Maps LDAP users to arrays that can be used to create new Piwik
 * users.
 *
 * See {@link UserSynchronizer} for more information.
 */
class UserMapper
{
    /**
     * The prefix for the 'password' field of a Piwik user that was converted to an LDAP
     * user. This prefix serves two functions: it identifies a user as an LDAP user and
     * hides the hashing algorithm used in LDAP.
     */
    const MAPPED_USER_PASSWORD_PREFIX = "{LDAP}";

    /**
     * The LDAP resource field that holds a user's username.
     *
     * @var string
     */
    private $ldapUserIdField = 'uid';

    /**
     * The LDAP resource field to use when determining a user's alias.
     *
     * @var string
     */
    private $ldapAliasField = 'cn';

    /**
     * The LDAP resource field to use when determining a user's email address.
     *
     * @var string
     */
    private $ldapMailField = 'mail';

    /**
     * The LDAP resource field to use when determining a user's first name.
     *
     * @var string
     */
    private $ldapFirstNameField = 'givenName';

    /**
     * The LDAP resource field to use when determining a user's last name.
     *
     * @var string
     */
    private $ldapLastNameField = 'sn';

    /**
     * Suffix to be appended to user names of LDAP users that have no email address.
     * Email addresses are required for Piwik users, so something must be entered.
     *
     * @var string
     */
    private $userEmailSuffix = '@mydomain.com';

    /**
     * Creates an array with normal Piwik user information using LDAP data for the user. The
     * information in the result should be used with the **UsersManager.addUser** API method.
     *
     * This method is used in syncing LDAP user information with Piwik user info.
     *
     * @param string[] $ldapUser Associative array containing LDAP field data, eg, `array('dn' => '...')`
     * @return string[]
     */
    public function createPiwikUserFromLdapUser($ldapUser)
    {
        $login = $this->getRequiredLdapUserField($ldapUser, $this->ldapUserIdField);

        return array(
            'login' => $login,
            'password' => $this->getPiwikPasswordForLdapUser($ldapUser),
            'email' => $this->getEmailAddressForLdapUser($ldapUser, $login),
            'alias' => $this->getAliasForLdapUser($ldapUser)
        );
    }

    /**
     * The password we store for a mapped user isn't used to authenticate, it's just
     * data used to generate a user's token auth.
     *
     * TODO: maybe it's better to create a random password for token auth
     */
    private function getPiwikPasswordForLdapUser($ldapUser)
    {
        $password = $this->getRequiredLdapUserField($ldapUser, 'userpassword');
        $password = preg_replace("/^(?:\\{[^}]*\\})?/", self::MAPPED_USER_PASSWORD_PREFIX, $password);
        $password = substr($password, 0, 32);
        $password = str_pad($password, 32, '-');
        return $password;
    }

    private function getEmailAddressForLdapUser($ldapUser, $login)
    {
        $email = $this->getLdapUserField($ldapUser, $this->ldapMailField);
        if (empty($email)) { // a valid email is needed to create a new user
            $email = $login . $this->userEmailSuffix;
        }
        return $email;
    }

    private function getAliasForLdapUser($ldapUser)
    {
        $alias = $this->getLdapUserField($ldapUser, $this->ldapAliasField);
        if (empty($alias)
            && !empty($ldapUser[$this->ldapFirstNameField])
            && !empty($ldapUser[$this->ldapLastNameField])
        ) {
            $alias = $this->getRequiredLdapUserField($ldapUser, $this->ldapFirstNameField)
                . ' '
                . $this->getRequiredLdapUserField($ldapUser, $this->ldapLastNameField);
        }
        return $alias;
    }

    private function getRequiredLdapUserField($ldapUser, $fieldName, $fetchSingleValue = true)
    {
        if (!isset($ldapUser[$fieldName])) {
            throw new Exception("LDAP entity missing required '$fieldName' field.");
        }

        return $this->getLdapUserField($ldapUser, $fieldName, $fetchSingleValue);
    }

    private function getLdapUserField($ldapUser, $fieldName, $fetchSingleValue = true)
    {
        $result = @$ldapUser[$fieldName];
        if ($fetchSingleValue
            && is_array($result)
        ) {
            $result = reset($result);
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getLdapUserIdField()
    {
        return $this->ldapUserIdField;
    }

    /**
     * @param string $ldapUserIdField
     */
    public function setLdapUserIdField($ldapUserIdField)
    {
        $this->ldapUserIdField = $ldapUserIdField;
    }

    /**
     * Returns the {@link $ldapAliasField} property.
     *
     * @return string
     */
    public function getLdapAliasField()
    {
        return $this->ldapAliasField;
    }

    /**
     * Sets the {@link $ldapAliasField} property.
     *
     * @param string $ldapAliasField
     */
    public function setLdapAliasField($ldapAliasField)
    {
        $this->ldapAliasField = $ldapAliasField;
    }

    /**
     * Returns the {@link $ldapMailField} property.
     *
     * @return string
     */
    public function getLdapMailField()
    {
        return $this->ldapMailField;
    }

    /**
     * Sets the {@link $ldapMailField} property.
     *
     * @param string $ldapMailField
     */
    public function setLdapMailField($ldapMailField)
    {
        $this->ldapMailField = $ldapMailField;
    }

    /**
     * Returns the {@link $ldapFirstNameField} property.
     *
     * @return string
     */
    public function getLdapFirstNameField()
    {
        return $this->ldapFirstNameField;
    }

    /**
     * Sets the {@link $ldapFirstNameField} property.
     *
     * @param string $ldapFirstNameField
     */
    public function setLdapFirstNameField($ldapFirstNameField)
    {
        $this->ldapFirstNameField = $ldapFirstNameField;
    }

    /**
     * Returns the {@link $ldapLastNameField} property.
     *
     * @return string
     */
    public function getLdapLastNameField()
    {
        return $this->ldapLastNameField;
    }

    /**
     * Sets the {@link $ldapLastNameField} property.
     *
     * @param string $ldapLastNameField
     */
    public function setLdapLastNameField($ldapLastNameField)
    {
        $this->ldapLastNameField = $ldapLastNameField;
    }

    /**
     * Returns the {@link $userEmailSuffix} property.
     *
     * @return string
     */
    public function getUserEmailSuffix()
    {
        return $this->userEmailSuffix;
    }

    /**
     * Sets the {@link $userEmailSuffix} property.
     *
     * @param string $userEmailSuffix
     */
    public function setUserEmailSuffix($userEmailSuffix)
    {
        $this->userEmailSuffix = $userEmailSuffix;
    }

    /**
     * Returns true if the user information is for a Piwik user that was mapped from LDAP,
     * false if otherwise.
     *
     * @param string[] $user The user information (must have at least a 'password' field).
     * @return bool
     * @throws Exception if the 'password' field is missing from $user.
     */
    public static function isUserLdapUser($user)
    {
        if (empty($user['password'])) { // sanity check
            throw new Exception("Unexpected error: no password for user, cannot check if LDAP synchronized.");
        }

        return substr($user['password'], 0, strlen(self::MAPPED_USER_PASSWORD_PREFIX)) == self::MAPPED_USER_PASSWORD_PREFIX;
    }

    /**
     * Creates a UserMapper instance configured using INI options.
     *
     * @return UserMapper
     */
    public static function makeConfigured()
    {
        $result = new UserMapper();

        $uidField = Config::getLdapUserIdField();
        if (!empty($uidField)) {
            $result->setLdapUserIdField($uidField);
        }

        $lastNameField = Config::getLdapLastNameField();
        if (!empty($lastNameField)) {
            $result->setLdapLastNameField($lastNameField);
        }

        $firstNameField = Config::getLdapFirstNameField();
        if (!empty($firstNameField)) {
            $result->setLdapFirstNameField($firstNameField);
        }

        $aliasField = Config::getLdapAliasField();
        if (!empty($aliasField)) {
            $result->setLdapAliasField($aliasField);
        }

        $mailField = Config::getLdapMailField();
        if (!empty($mailField)) {
            $result->setLdapMailField($mailField);
        }

        $userEmailSuffix = Config::getLdapUserEmailSuffix();
        if (!empty($userEmailSuffix)) {
            $result->setUserEmailSuffix($userEmailSuffix);
        }

        return $result;
    }
}