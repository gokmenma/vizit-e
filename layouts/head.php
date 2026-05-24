<?php
$page = $_GET['url'] ?? ''; 
$userRole = $_SESSION["role"] ?? "user";

if (defined('SPA_LAYOUT')) {
    return;
}

use App\Helper\Security;

Security::checkLogin();



?>

<!doctype html>
<html lang="tr">

</html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="description" content="Sgk Rapor işlemlerinizi sms şifresi olmadan yapın">

    <title>Vizit-E / <?php echo $title ?? "Ana Sayfa" ?></title>
    <link rel="icon" href="assets/images/logo.svg" type="image/svg+xml">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

    <!-- Favicon-->
    <!-- <link rel="stylesheet" href="assets/plugins/bootstrap/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous"> -->

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.4/dist/sweetalert2.min.css" />

    <link rel="stylesheet" href="assets/plugins/bootstrap-select/css/bootstrap-select.css" />



    <!-- Custom Css2 -->
    <!-- <link rel="stylesheet" href="assets/css/main.css?v=<?= filemtime('assets/css/main.css') ?>"> -->

    <!-- <link rel="stylesheet" href="assets/css/color_skins.css"> -->
    <!-- <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>"> -->

    <!-- //Cannocial ekle -->
    <link rel="canonical" href="https://vizit-e.com" />


    <!-- Datatable.js -->
    <?php
    if ($page == '') {
        // echo '<link rel="stylesheet" href="assets/plugins/datatables/dataTables.min.css">';
    }

    ?>



</head>

<body class="theme-black">