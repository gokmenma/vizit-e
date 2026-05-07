<?php

require_once "vendor/autoload.php";



use App\Helper\Security;
use App\Helper\Date;
use Models\UserModel;


$title = "Kullanıcılar";

$UserModel = new UserModel();

$kullanicilar = $UserModel->all();


Security::checkAdmin("sign-in");

?>

<?php require "admin/layouts/head.php"; ?>

<?php require "admin/layouts/preloader.php"; ?>

<div class="overlay"></div><!-- Overlay For Sidebars -->

<?php require "admin/layouts/topbar.php"; ?>
<?php require "admin/layouts/navbar.php"; ?>

<!-- Main Content -->
<section class="content">
    <div class="container">
        <div class="block-header">
            <div class="row clearfix">
                <div class="col-lg-5 col-md-5 col-sm-12">
                    <h2>Projects List</h2>                    
                </div>            
                <div class="col-lg-7 col-md-7 col-sm-12">
                    <ul class="breadcrumb float-md-right padding-0">
                        <li class="breadcrumb-item"><a href="index.html"><i class="zmdi zmdi-home"></i></a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Pages</a></li>
                        <li class="breadcrumb-item active">Projects List</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row clearfix">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="body project_report">
                        <div class="table-responsive">
                            <table class="table m-b-0 table-hover">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Adı Soyadı</th>
                                        <th>Kullanıcı Adı</th>
                                        <th>Telefon</th>
                                        <th>Email</th>
                                        
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kullanicilar as $kullanici):
                                       // $badge_color = $abonelik->durum == 'aktif' ? 'success' : ($abonelik->durum == 'pasif' ? 'danger' : 'warning');

                                        ?>

                                    <tr>
                                        <td>
                                            <span class="badge badge-<?php echo $badge_color; ?> m-r-10">
                                                <?php echo $kullanici->adi_soyadi; ?>
                                            </span>
                                        </td>
                                        <td class="project-title">
                                            <a href="#"><?php echo $kullanici->kullanici_adi; ?></a>

                                        </td>
                                        <td>
                                            <a href="#"><?php echo $kullanici->telefon; ?></a>

                                        </td>
                                        <td>
                                            <a href="#"><?php echo $kullanici->email; ?></a>

                                        </td>
                                        <td class="project-actions">
                                            <a href="#" class="btn btn-neutral btn-sm"><i class="zmdi zmdi-eye"></i></a>
                                            <a href="#" class="btn btn-neutral btn-sm"><i class="zmdi zmdi-edit"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require "admin/layouts/vendor-scripts.php"; ?>
<?php require "admin/layouts/foot.php"; ?>
