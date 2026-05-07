<?php

require_once "vendor/autoload.php";



use App\Helper\Security;
use App\Helper\Date;
use Models\KullaniciAbonelikModel;


$title = "Kullanıcı Abonelikleri";

$KullaniciAbonelikModel = new KullaniciAbonelikModel();

$kullanici_abonelikleri = $KullaniciAbonelikModel->getAllSubscriptionsWithUsernames();


//Security::checkAdmin("sign-in");

?>

<?php require "layouts/head.php"; ?>

<?php require "layouts/preloader.php"; ?>

<div class="overlay"></div><!-- Overlay For Sidebars -->

<?php require "layouts/topbar.php"; ?>
<?php require "layouts/navbar.php"; ?>

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
                                        <th>Project</th>
                                        <th>Prograss</th>
                                        <th>Team</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kullanici_abonelikleri as $abonelik):
                                        $badge_color = $abonelik->durum == 'aktif' ? 'success' : ($abonelik->durum == 'pasif' ? 'danger' : 'warning');

                                        ?>

                                    <tr>
                                        <td>
                                            <span class="badge badge-<?php echo $badge_color; ?> m-r-10">
                                                <?php echo $abonelik->durum; ?>
                                            </span>
                                        </td>
                                        <td class="project-title">
                                            <h6><a href="#"><?php echo $abonelik->paket_adi; ?></a></h6>

                                            <small>Created 18.Mar.2018</small>
                                        </td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar l-dark" role="progressbar" aria-valuenow="23" aria-valuemin="0" aria-valuemax="100" style="width: 23%;"></div>                                                
                                            </div>
                                            <small>Completion with: 23%</small>
                                        </td>
                                        <td>
                                            <ul class="list-unstyled team-info">
                                                <li><img src="assets/images/xs/avatar1.jpg" alt="Avatar"></li>
                                                <li><img src="assets/images/xs/avatar5.jpg" alt="Avatar"></li>
                                            </ul>
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

<?php require "layouts/vendor-scripts.php"; ?>
<?php require "layouts/foot.php"; ?>
