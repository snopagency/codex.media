<?php defined('SYSPATH') or die('No direct script access.');

class Controller_User_Modify extends Controller_Base_preDispatch
{
    const TOGGLE_BAN = -1;
    const TOGGLE_PROMOTE = 2;

    public function action_settings() {

        if (!$this->user->id) {
            throw new HTTP_Exception_403();
        }

        $this->view['success'] = Arr::get($_GET, 'success', 0);

        if (Security::check(Arr::get($_POST, 'csrf'))) {
           $this->view['success'] = $this->update();
        };

        $this->template->content = View::factory('/templates/users/settings', $this->view);

    }

    public function update () {

        $name = Arr::get($_POST, 'name', $this->user->name);
        $bio  = Arr::get($_POST, 'bio', $this->user->bio);

        $fields = array(
            'name' => $name,
            'bio'  => $bio,
        );

        if ($this->validateForm($fields)) {

            $this->user->updateUser($this->user->id, $fields);
            $this->redirect('user/settings?success=1');
            return true;

        }

        return false;

    }

    private function validateForm($fields) {

        $success = true;

        if (!trim($fields['name'])) {
            $this->view['errors']['name'] = 'Введите имя';
            $success = false;
        }

        return $success;

    }

    public function action_request_password_change() {

        $this->auto_render = false;

        $response = array(
            'success' => 0
        );

        $request  = json_decode(file_get_contents('php://input'));
        $password = $request->currentPassword;
        $csrf     = $request->csrf;

        if (!Security::check($csrf) || !$this->request->is_ajax()) {
            throw new HTTP_Exception_403();
        }

        if (empty($password)) {

            $response['message'] = 'Введите пароль';
            $this->response->body(json_encode($response));
            return;

        }

        if (!$this->user->checkPassword($password)) {

            $response['message'] = 'Неверный пароль';
            $this->response->body(json_encode($response));
            return;

        }


        $model_auth = new Model_Auth($this->user);

        $model_auth->sendChangePasswordEmail();

        $response = array(
            'success' => 1,
            'message' => 'Мы отправили на вашу почту письмо с подтверждением. Перейдите по ссылке в письме, чтобы установить новый пароль.'
        );

        $this->response->body(json_encode($response));

    }

    /**
     * Fast saving bio from profile
     * AJAX action
     */
    public function action_updateBio()
    {
        $response = array(
            'success' => 0
        );

        $bio  = Arr::get($_POST, 'bio');
        $csrf = Arr::get($_POST, 'csrf');

        $bio = trim($bio);

        if (Security::check($csrf) && $bio) {

            $saving = $this->user->updateUser($this->user->id, array(
                'bio' => $bio
            ));

            $response['success'] = 1;
            $response['bio']     = $bio;
            $response['csrf']    = Security::token(true);

        }

        $this->auto_render = false;
        $this->response->headers('Content-Type', 'application/json; charset=utf-8');
        $this->response->body( json_encode($response) );
    }


    public function action_changeStatus()
    {
        $response = array();

        if (!$this->user->isAdmin) {

            $response['success'] = 0;
            $response['message'] = 'Access denied';
            goto finish;

        }

        $response['success'] = 1;
        $userId = Arr::get($_GET, 'userId', 0);
        $status = Arr::get($_GET, 'status', '');

        $viewUser = new Model_User($userId);

        switch ($status) {
            case self::TOGGLE_BAN:

                $newStatus =
                    $viewUser->status != Model_User::USER_STATUS_BANNED ?
                    Model_User::USER_STATUS_BANNED :
                    Model_User::USER_STATUS_REGISTERED;

                break;

            case self::TOGGLE_PROMOTE:

                $newStatus =
                    $viewUser->status != Model_User::USER_STATUS_TEACHER ?
                    Model_User::USER_STATUS_TEACHER :
                    Model_User::USER_STATUS_REGISTERED;

                break;

            default:
                break;
        }

        $viewUser->updateUser(
            $viewUser->id,
            array('status' => $newStatus)
        );

        switch ($newStatus) {
            case Model_User::USER_STATUS_BANNED:
                $response['message'] = 'Пользователь заблокирован';
                break;

            case Model_User::USER_STATUS_REGISTERED:
                $response['message'] = 'Установлен обычный статус пользователя';
                break;

            case Model_User::USER_STATUS_TEACHER:
                $response['message'] = 'Пользователь добавлен в группы "Учителя"';
                break;
        }

        finish:
        $this->auto_render = false;
        $this->response->headers('Content-Type', 'application/json; charset=utf-8');
        $this->response->body( json_encode($response) );
    }

}
