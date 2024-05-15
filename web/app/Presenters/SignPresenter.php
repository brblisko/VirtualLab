<?php
/**
 * Application    VirtualLab
 * Author         Boris Vesely
 */


declare(strict_types=1);

namespace App\Presenters;

use Nette;
use App\Models\Authenticator;

class SignPresenter extends Nette\Application\UI\Presenter
{
    private $authenticator;

    // Constructor to initialize the authenticator
    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    // Method to render the sign-in form
    public function renderIn()
    {
        // Render sign-in form
    }

    // Method to create the sign-in form component
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

    // Method called on successful form submission
    public function signInFormSucceeded(Nette\Application\UI\Form $form, \stdClass $values)
    {
        try {
            $this->getUser()->login($values->username, $values->password);
            $this->redirect('LandingPage:welcome');
        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }

    // Method to handle user sign out
    public function actionOut(): void
    {
        $this->getUser()->logout();
        $this->flashMessage('You have been signed out.');
        $this->redirect('LandingPage:welcome');
    }
}
