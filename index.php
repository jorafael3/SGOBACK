<?php
require_once "libs/database.php";
require_once "libs/controller.php";
require_once "libs/view.php";
require_once "libs/model.php";
require_once "libs/app.php";
require_once "config/config.php";
include_once 'includes/user.php';
include_once 'includes/user_session.php';

$userSession = new User_Session();
$user = new User();

$data = new Database();

if ($data->connect()) {
    //echo "true";
    $app = new App();
} else {
    //include_once 'views/errores/500.php';
}
