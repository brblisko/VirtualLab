<?php
namespace App\Models;

use Nette;
use Nette\Security\SimpleIdentity;

class Authenticator implements Nette\Security\Authenticator
{
    private $database;
    private $passwords;

    public function __construct(
        Nette\Database\Explorer $database,
        Nette\Security\Passwords $passwords
    )
    {
        $this->database = $database;
        $this->passwords = $passwords;
    }


    public function authenticate(string $username, string $password): SimpleIdentity
    {
        $row = $this->database->table('users')
            ->where('username', $username)
            ->fetch();
        
        if(!$row)
        {
            throw new Nette\Security\AuthenticationException('User not found.');
        }

        if(!$this->passwords->verify($password, $row->password))
        {
            throw new Nette\Security\AuthenticationException('Invalid Password.');
        }


        if ($row->username === "admin") 
        {
            return new SimpleIdentity(
                $row->id,
                "admin",
                ['username' => $row->username]
            );
        }

        return new SimpleIdentity(
            $row->id,
            null,
            ['username' => $row->username]
        );
    }

    public function createUser(string $username, string $password)
    {
        $this->database
            ->table('users')
            ->insert(['username' => $username, 'password' => $this->passwords->hash($password)]);
    }
}