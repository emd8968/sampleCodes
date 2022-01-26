<?php

namespace App;


use App\Enums\UserTypes;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Crypt;

class User implements Authenticatable
{

    protected $identifierName = 'complete';
    protected $username = null;
    protected $password = null;
    protected $userType = null;
    protected $userLang = null;
    protected $userRights = null;

    public static function createByIdentity($identity)
    {
        $complete = json_decode($identity, true);

        $complete['password'] = Crypt::decryptString($complete['password']);

        return new User($complete['username'], $complete['password'], $complete['userType'], $complete['userLang'], $complete['rights']);
    }

    public function __construct($username, $password, $userType, $userLang = null, $rights = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->userLang = $userLang;
        $this->userRights = $rights;

        switch ($userType) {
            case UserTypes::Administrator:
                $this->userType = UserTypes::Administrator;
                break;
            case UserTypes::Accounting:
                $this->userType = UserTypes::Accounting;
                break;
            case UserTypes::Reseller:
                $this->userType = UserTypes::Reseller;
                break;
            default:
                throw new \Exception('Not a valid user type!', 422);
        }
    }


    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return $this->identifierName;
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {

        $complete = [
            'username' => $this->username,
            'userType' => $this->userType,
            'userLang' => $this->userLang,
            'password' => $this->encryptPassword($this->password),
            'rights' => $this->userRights
        ];

        return json_encode($complete);
    }

    protected function encryptPassword($password)
    {
        $ciphertext = Crypt::encryptString($password);

        return $ciphertext;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getUserType()
    {
        return $this->userType;
    }

    public function getUserLang()
    {
        return $this->userLang;
    }

    public function getUserRights()
    {
        return $this->userRights;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        return null;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string $value
     * @return void
     */
    public function setRememberToken($value)
    {

    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return null;
    }

    public function getDisplayName()
    {
        return $this->getUsername();
    }

}
