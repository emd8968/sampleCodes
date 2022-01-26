<?php

namespace App\Providers;

use App\Enums\EngineResponseTypes;
use App\Enums\EngineActionTypes;
use App\Enums\UserRightTypes;
use App\Enums\UserTypes;
use App\Models\Engine\Administration\System\UserRight;
use App\User;
use App\Utils\EngineClient;
use \Illuminate\Contracts\Auth\UserProvider;
use  \Illuminate\Contracts\Auth\Authenticatable;
use  \Illuminate\Support\Facades\Log;

class EngineUserProvider implements UserProvider
{


    /**
     * @param  mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $user = User::createByIdentity($identifier);

        return $user;
    }

    /**
     * @param  mixed $identifier
     * @param  string $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        throw new \Exception('Not implemented!');
    }

    /**
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
		
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $client = new EngineClient();

        $rights = [];
        switch ($credentials['userType']) {

            case UserTypes::Administrator:

                $res = $client->request(EngineActionTypes::Authenticate, $credentials, EngineResponseTypes::Raw);
                if (!$res->isError()) {
                    $rights = ($client->request(EngineActionTypes::Rights, $credentials, EngineResponseTypes::SingleItem))->getBodyAsArray();
                }
                break;
            default:
                $res = $client->request(null, $credentials, EngineResponseTypes::Raw);
        }
		
        if (!$res->isError()) {
			
            return new User($credentials['username'], $credentials['password'], $credentials['userType'], $credentials['userLang'], $rights);
        } else {
            return null;
        }
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  array $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $rights = $user->getUserRights();

        if ($user->getUserType() === UserTypes::Administrator) {
            if (($rights['Rights'] === UserRightTypes::ReadOnly || $rights['Rights'] === UserRightTypes::ReadWrite) && $rights['Permit'] !== 0xFFFFFFFF) {

                throw new \Exception('User doesnt have enough permission to use this panel!', 422);
            }
        }

        $retVal = $user->getAuthPassword() === $credentials["password"] &&
            $user->getUsername() === $credentials["username"] &&
            $user->getUserType() === $credentials["userType"];


        return $retVal;
    }
}