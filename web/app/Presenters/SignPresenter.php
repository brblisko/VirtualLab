<?php

namespace App\Presenters;

use Nette;
use App\Models\Authenticator;

class SignPresenter extends Nette\Application\UI\Presenter
{
    private $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public function renderIn()
    {
        // Render sign-in form
    }

    public function renderUp()
    {
        // Render sign-up form
    }

    protected function createComponentSignInForm()
    {
        $form = new Nette\Application\UI\Form;

        $form->addText('username', 'Username:')
            ->setRequired('Please enter your username.');

        $form->addPassword('password', 'Password:')
            ->setRequired('Please enter your password.');

        $form->addSubmit('signIn', 'Sign In');

        $form->onSuccess[] = [$this, 'signInFormSucceeded'];

        return $form;
    }

    protected function createComponentSignUpForm()
    {
        $form = new Nette\Application\UI\Form;

        $form->addText('username', 'Username:')
            ->setRequired('Please choose a username.');

        $form->addPassword('password', 'Password:')
            ->setRequired('Please choose a password.');

        $form->addSubmit('signUp', 'Sign Up');

        $form->onSuccess[] = [$this, 'signUpFormSucceeded'];

        return $form;
    }

    public function signInFormSucceeded(Nette\Application\UI\Form $form, \stdClass $values)
    {
        try {
            $this->getUser()->login($values->username, $values->password);
            $this->redirect('LandingPage:welcome');
        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }

    public function signUpFormSucceeded(Nette\Application\UI\Form $form, \stdClass $values)
    {
        try {
            $this->authenticator->createUser($values->username, $values->password);
            $this->getUser()->login($values->username, $values->password);
            $this->redirect('LandingPage:welcome');
        } catch (\PDOException $e) {
            $form->addError('Username already exists.');
        }
    }

    public function actionOut(): void
    {
        $this->getUser()->logout();
        $this->flashMessage('You have been signed out.');
        $this->redirect('Landingpage:welcome');
    }
}