<?php

require_once "../vendor/autoload.php";


use App\Helper\Security;
use App\Helper\Date;
use Models\KullaniciAbonelikModel;

session_start();

$title = "Ana Sayfa";

$KullaniciAbonelikModel = new KullaniciAbonelikModel();

$kullanici_abonelikleri = $KullaniciAbonelikModel->getAllSubscriptionsWithUsernames();

// Admin sayfasına erişim kontrolü yapılıyor

echo "sesion " . $_SESSION['admin_id'] ?? null;

Security::checkAdmin("sign-in");

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
                    <h2>App Widgets</h2>                    
                </div>            
                <div class="col-lg-7 col-md-7 col-sm-12">
                    <ul class="breadcrumb float-md-right padding-0">
                        <li class="breadcrumb-item"><a href="index.html"><i class="zmdi zmdi-home"></i></a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Pages</a></li>
                        <li class="breadcrumb-item active">App Widgets</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row clearfix">
            <div class="col-lg-4 col-md-12">
                <div class="card project_widget">                    
                    <div class="body">
                        <div class="row pw_content">
                            <div class="col-12 pw_header">
                                <h6>Toplam Kullanıcı Sayısı</h6>
                                <small class="text-muted">Alpino  |  Last Update: 12 Dec 2017</small>
                            </div>
                            <div class="col-8 pw_meta">
                                <span>4,870 USD</span>                                
                                <small class="text-danger">17 Days Remaining</small>
                            </div>
                            <div class="col-4">
                                <div class="sparkline m-t-10" data-type="bar" data-width="97%" data-height="26px" data-bar-Width="2" data-bar-Spacing="7" data-bar-Color="#7460ee">2,5,6,3,4,5,5,6,2,1</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card project_widget">
                    <div class="body">
                        <div class="row pw_content">
                            <div class="col-12 pw_header">
                                <h6>New Dashboard</h6>
                                <small class="text-muted">Alpino  |  Last Update: 17 Dec 2017</small>
                            </div>
                            <div class="col-8 pw_meta">
                                <span>4,210 USD</span>                                
                                <small class="text-success">Early Dec 2017</small>
                            </div>
                            <div class="col-4">
                                <div class="sparkline m-t-10" data-type="bar" data-width="97%" data-height="26px" data-bar-Width="2" data-bar-Spacing="7" data-bar-Color="#60bafd">2,5,6,3,4,5,5,6,2,1</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card project_widget">
                    <div class="body">
                        <div class="row pw_content">
                            <div class="col-12 pw_header">
                                <h6>Mobile App</h6>
                                <small class="text-muted">Alpino  |  Last Update: 21 Dec 2017</small>
                            </div>
                            <div class="col-8 pw_meta">
                                <span>1,870 USD</span>                                
                                <small class="text-danger">10 Days Remaining</small>
                            </div>
                            <div class="col-4">
                                <div class="sparkline m-t-10" data-type="bar" data-width="97%" data-height="26px" data-bar-Width="2" data-bar-Spacing="7" data-bar-Color="#000000">2,3,6,5,4,5,8,7,6,3</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row clearfix">
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="header">
                        <h2><strong>User</strong> Activities</h2>
                    </div>
                    <div class="body m-b-10 widget-user">
                        <img class="rounded-circle" src="assets/images/sm/avatar5.jpg" alt="">
                        <div class="wid-u-info">
                            <h5>Monica Ryther</h5>
                            <p class="text-muted m-b-0">info@example.com</p>
                            <small class="text-warning"><b>Developer</b></small>
                        </div>
                    </div>
                    <div class="body activities">
                        <div class="streamline b-accent">
                            <div class="sl-item">
                                <div class="sl-content">
                                    <div class="text-muted">Just now</div>
                                    <p>Finished task <a href="" class="text-info">#features 4</a>.</p>
                                </div>
                            </div>
                            <div class="sl-item b-info">
                                <div class="sl-content">
                                    <div class="text-muted">10:30</div>
                                    <p><a href="">@Jessi</a> retwit your post</p>
                                </div>
                            </div>
                            <div class="sl-item b-primary">
                                <div class="sl-content">
                                    <div class="text-muted">12:30</div>
                                    <p>Call to customer <a href="" class="text-info">Jacob</a> and discuss the detail.</p>
                                </div>
                            </div>
                            <div class="sl-item b-warning">
                                <div class="sl-content">
                                    <div class="text-muted">1 days ago</div>
                                    <p><a href="" class="text-info">Jessi</a> commented your post.</p>
                                </div>
                            </div>
                            <div class="sl-item b-primary">
                                <div class="sl-content">
                                    <div class="text-muted">2 days ago</div>
                                    <p>Call to customer <a href="" class="text-info">Jacob</a> and discuss the detail.</p>
                                </div>
                            </div>
                            <div class="sl-item b-primary">
                                <div class="sl-content">
                                    <div class="text-muted">3 days ago</div>
                                    <p>Call to customer <a href="" class="text-info">Jacob</a> and discuss the detail.</p>
                                </div>
                            </div>
                            <div class="sl-item b-warning">
                                <div class="sl-content">
                                    <div class="text-muted">4 Week ago</div>
                                    <p><a href="" class="text-info">Jessi</a> commented your post.</p>
                                </div>
                            </div>
                            <div class="sl-item b-warning">
                                <div class="sl-content">
                                    <div class="text-muted">5 days ago</div>
                                    <p><a href="" class="text-info">Jessi</a> commented your post.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 col-md-12">
                <div class="card">
                    <div class="header">
                        <h2><strong>Earning</strong> Report</h2>
                        <ul class="header-dropdown">
                            <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more"></i> </a>
                                <ul class="dropdown-menu slideUp">
                                    <li><a href="javascript:void(0);">Action</a></li>
                                    <li><a href="javascript:void(0);">Another action</a></li>
                                    <li><a href="javascript:void(0);">Something else</a></li>
                                    <li><a role="button" class="boxs-close">Delete</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div class="body m-b-10 bg-dark">
                        <div class="row">
                            <div class="col-6">
                                <small>Total Earning</small>
                                <h4 class="text-success m-b-0 m-t-0">$7,171</h4>
                                <h6 class="m-b-0 m-t-0">March 2018</h6>
                            </div>
                            <div class="col-6 text-right">
                                <div class="sparkline m-t-10" data-type="bar" data-width="97%" data-height="50px" data-bar-Width="2" data-bar-Spacing="7" data-bar-Color="#18ce0f ">2,5,6,3,4,5,5,6,2,1</div>                                
                            </div>
                        </div>
                    </div>
                    <div class="body">
                        <div class="table-responsive earning-report">
                            <table class="table m-b-0 table-hover">
                                <thead>
                                    <tr>
                                        <th colspan="2">User Name</th>
                                        <th>Priority</th>
                                        <th>Earnings</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="width:60px;"><span class="rounded"><img src="assets/images/xs/avatar1.jpg" alt="user" width="50"></span></td>
                                        <td>
                                            <h6>John Smith</h6><small class="text-muted">UI UX Designer</small></td>
                                        <td><span class="badge badge-success">Low</span></td>
                                        <td>$1.9K</td>
                                    </tr>
                                    <tr class="active">
                                        <td><span class="rounded"><img src="assets/images/xs/avatar2.jpg" alt="user" width="50"></span></td>
                                        <td>
                                            <h6>Hossein Shams</h6><small class="text-muted">Project Manager</small></td>
                                        <td><span class="badge badge-info">Medium</span></td>
                                        <td>$2.9K</td>
                                    </tr>
                                    <tr>
                                        <td><span class="round round-success"><img src="assets/images/xs/avatar3.jpg" alt="user" width="50"></span></td>
                                        <td>
                                            <h6>Maryam Amiri</h6><small class="text-muted">Angular Developer</small></td>
                                        <td><span class="badge badge-primary">High</span></td>
                                        <td>$32.9K</td>
                                    </tr>
                                    <tr>
                                        <td><span class="round round-primary"><img src="assets/images/xs/avatar4.jpg" alt="user" width="50"></span></td>
                                        <td>
                                            <h6>Tim Hank</h6><small class="text-muted">Frontend</small></td>
                                        <td><span class="badge badge-danger">Low</span></td>
                                        <td>$11.9K</td>
                                    </tr>
                                    <tr>
                                        <td><span class="round round-warning"><img src="assets/images/xs/avatar5.jpg" alt="user" width="50"></span></td>
                                        <td>
                                            <h6>Fidel Tonn</h6><small class="text-muted">Content Writer</small></td>
                                        <td><span class="badge badge-warning">High</span></td>
                                        <td>$2.5K</td>
                                    </tr>
                                    <tr>
                                        <td><span class="round round-danger"><img src="assets/images/xs/avatar6.jpg" alt="user" width="50"></span></td>
                                        <td>
                                            <h6>Frank Camly</h6><small class="text-muted">Graphic Design</small></td>
                                        <td><span class="badge badge-info">High</span></td>
                                        <td>$12.7K</td>
                                    </tr>
                                    <tr>
                                        <td><span class="round round-primary"><img src="assets/images/xs/avatar4.jpg" alt="user" width="50"></span></td>
                                        <td>
                                            <h6>Tim Hank</h6><small class="text-muted">Frontend</small></td>
                                        <td><span class="badge badge-danger">Low</span></td>
                                        <td>$11.9K</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>    
            </div>
        </div>        
        <div class="row clearfix">
            <div class="col-md-12 col-lg-4">
                <div class="card">
                    <div class="header">
                        <h2><strong>Invoice</strong></h2>
                        <ul class="header-dropdown">
                            <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more"></i> </a>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="javascript:void(0);">Action</a></li>
                                    <li><a href="javascript:void(0);">Another action</a></li>
                                    <li><a href="javascript:void(0);">Something else</a></li>
                                    <li><a href="javascript:void(0);" class="boxs-close">Delete</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div class="body">
                        <div class="row">
                            <div class="col-md-12">
                                <address>
                                    <strong>ThemeMakker Inc.</strong> <small class="float-right">25.Mar.2018</small><br>
                                    795 Folsom Ave, Suite 546,<br> San Francisco, CA 54656<br>
                                    <abbr title="Phone">P:</abbr> (123) 456-34636
                                </address>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table m-b-0 table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Project</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <td></td>
                                    <td></td>
                                    <td><strong>$2745</strong></td>
                                </tfoot>
                                <tbody>
                                    <tr>
                                        <td>AD1</td>
                                        <td>AdminCC Dashboard</td>
                                        <td>$300</td>
                                    </tr>
                                    <tr>
                                        <td>FA8</td>
                                        <td>Falcon Admin</td>
                                        <td>$255</td>
                                    </tr>
                                    <tr>
                                        <td>OB4</td>
                                        <td>Oreo Bootstrap 4</td>
                                        <td>$530</td>
                                    </tr>
                                    <tr>
                                        <td>OH4</td>
                                        <td>Oreo Hospital</td>
                                        <td>$615</td>
                                    </tr>
                                    <tr>
                                        <td>OA5</td>
                                        <td>Oreo Angular 5</td>
                                        <td>$920</td>
                                    </tr>
                                    <tr>
                                        <td>IO3</td>
                                        <td>iNext OnePage HTMl</td>
                                        <td>$125</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button class="btn btn-warning btn-icon btn-icon-mini btn-round"><i class="zmdi zmdi-print"></i></button>
                                <button class="btn btn-primary btn-round">Pay Now</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-lg-4">
                <div class="card">
                    <div class="header">
                        <h2><strong>Resent</strong> Email</h2>
                        <ul class="header-dropdown">
                            <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more"></i> </a>
                                <ul class="dropdown-menu slideUp">
                                    <li><a href="javascript:void(0);">Action</a></li>
                                    <li><a href="javascript:void(0);">Another action</a></li>
                                    <li><a href="javascript:void(0);">Something else</a></li>
                                    <li><a href="javascript:void(0);" class="boxs-close">Deletee</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div class="body">
                        <ul class="inbox-widget list-unstyled clearfix">
                            <li class="inbox-inner"><a href="javascript:void(0)">
                                <div class="inbox-item">
                                    <div class="inbox-img"> <img src="assets/images/xs/avatar1.jpg" class="rounded" alt=""> </div>
                                    <div class="inbox-item-info">
                                        <p class="author">Aaron	Enlightened</p>
                                        <p class="inbox-message">[ThemeForest]is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout.</p>
                                        <p class="inbox-date">13:34 PM</p>
                                        <div class="hover_action">
                                            <button class="btn btn-primary btn-sm"><i class="zmdi zmdi-eye"></i></button>
                                            <button class="btn btn-primary btn-sm"><i class="zmdi zmdi-delete"></i></button>
                                        </div>
                                    </div>
                                </div>
                                </a>
                            </li>                            
                            <li class="inbox-inner"><a href="javascript:void(0)">
                                <div class="inbox-item">
                                    <div class="inbox-img"> <img src="assets/images/xs/avatar3.jpg" class="rounded" alt=""> </div>
                                    <div class="inbox-item-info">
                                        <p class="author">Austin</p>
                                        <p class="inbox-message">[Google]Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>
                                        <p class="inbox-date">1Day ago</p>
                                        <div class="hover_action">
                                            <button class="btn btn-primary btn-sm"><i class="zmdi zmdi-eye"></i></button>
                                            <button class="btn btn-primary btn-sm"><i class="zmdi zmdi-delete"></i></button>
                                        </div>
                                    </div>
                                </div>
                                </a>
                            </li>
                            <li class="inbox-inner"><a href="javascript:void(0)">
                                <div class="inbox-item">
                                    <div class="inbox-img"> <img src="assets/images/xs/avatar4.jpg" class="rounded" alt=""> </div>
                                    <div class="inbox-item-info">
                                        <p class="author">John Benjamin</p>
                                        <p class="inbox-message">[WrapTheme]If you are going to use a passage of Lorem Ipsum, you need to be sure</p>
                                        <p class="inbox-date">1Day ago</p>
                                        <div class="hover_action">
                                            <button class="btn btn-primary btn-sm"><i class="zmdi zmdi-eye"></i></button>
                                            <button class="btn btn-primary btn-sm"><i class="zmdi zmdi-delete"></i></button>
                                        </div>
                                    </div>
                                </div>
                                </a>
                            </li>
                            <li class="inbox-inner"><a href="javascript:void(0)">
                                <div class="inbox-item">
                                    <div class="inbox-img"> <img src="assets/images/xs/avatar5.jpg" class="rounded" alt=""> </div>
                                    <div class="inbox-item-info">
                                        <p class="author">Broderick</p>
                                        <p class="inbox-message">[Awwwards]There are many variations of passages of Lorem Ipsum available, but the majority</p>
                                        <p class="inbox-date">Week ago</p>
                                        <div class="hover_action">
                                            <button class="btn btn-primary btn-sm"><i class="zmdi zmdi-eye"></i></button>
                                            <button class="btn btn-primary btn-sm"><i class="zmdi zmdi-delete"></i></button>
                                        </div>
                                    </div>
                                </div>
                                </a>
                            </li>
                            <li class="inbox-inner"><a href="javascript:void(0)">
                                <div class="inbox-item">
                                    <div class="inbox-img"> <img src="assets/images/xs/avatar6.jpg" class="rounded" alt=""> </div>
                                    <div class="inbox-item-info">
                                        <p class="author">Austin</p>
                                        <p class="inbox-message">[Google]Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>
                                        <p class="inbox-date">1Day ago</p>
                                        <div class="hover_action">
                                            <button class="btn btn-primary btn-sm"><i class="zmdi zmdi-eye"></i></button>
                                            <button class="btn btn-primary btn-sm"><i class="zmdi zmdi-delete"></i></button>
                                        </div>
                                    </div>
                                </div>
                                </a>
                            </li>
                            <li class="inbox-inner"><a href="javascript:void(0)">
                                <div class="inbox-item">
                                    <div class="inbox-img"> <img src="assets/images/xs/avatar7.jpg" class="rounded" alt=""> </div>
                                    <div class="inbox-item-info">
                                        <p class="author">John Benjamin</p>
                                        <p class="inbox-message">[WrapTheme]If you are going to use a passage of Lorem Ipsum, you need to be sure</p>
                                        <p class="inbox-date">1Day ago</p>
                                        <div class="hover_action">
                                            <button class="btn btn-primary btn-sm"><i class="zmdi zmdi-eye"></i></button>
                                            <button class="btn btn-primary btn-sm"><i class="zmdi zmdi-delete"></i></button>
                                        </div>
                                    </div>
                                </div>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-lg-4">
                <div class="card">
                    <div class="header">
                        <h2><strong>Recent</strong> Chats</h2>
                    </div>
                    <div class="body">                        
                        <ul class="chat-widget m-r-5 clearfix">
                            <li class="left float-left">
                                <img src="assets/images/xs/avatar3.jpg" class="rounded-circle" alt="">
                                <div class="chat-info">
                                    <a class="name" href="javascript:void(0);">Alexander</a>
                                    <span class="datetime">11:12</span>
                                    <span class="message">Hello, Michael<br>What is the update on Eisenhower X?</span>
                                </div>
                            </li>
                            <li class="right">
                                <div class="chat-info"><span class="datetime">11:15</span> <span class="message">Hi, Alexander<br> It is almost completed. I will send you an email later today.</span> </div>
                            </li>
                            <li class="left float-left">
                                <img src="assets/images/xs/avatar3.jpg" class="rounded-circle" alt="">
                                <div class="chat-info">
                                    <a class="name" href="javascript:void(0);">Alexander</a>
                                    <span class="datetime">11:22</span>
                                    <span class="message">That's great. Will catch you in evening.</span>
                                </div>
                            </li>
                            <li class="right">
                                <div class="chat-info"><span class="datetime">11:25</span> <span class="message">That's great.<br>Sure we'will have a blast today.</span> </div>
                            </li>
                        </ul>
                        <div class="input-group p-t-15">
                            <input type="text" class="form-control" placeholder="Enter text here...">
                            <span class="input-group-addon"><i class="zmdi zmdi-mail-send"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row clearfix">
            <div class="col-md-12 col-lg-4">
                <div class="card">
                    <div class="header">
                        <h2><strong>Follow</strong> Us</h2>
                        <ul class="header-dropdown">
                            <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more"></i> </a>
                                <ul class="dropdown-menu slideUp">
                                    <li><a href="javascript:void(0);">Action</a></li>
                                    <li><a href="javascript:void(0);">Another action</a></li>
                                    <li><a href="javascript:void(0);">Something else</a></li>
                                    <li><a class="boxs-close">Delete</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div class="body">
                        <ul class="follow_us list-unstyled">
                            <li class="online">
                                <a href="javascript:void(0);">
                                    <div class="media">
                                        <img class="media-object " src="assets/images/xs/avatar4.jpg" alt="">
                                        <div class="media-body">
                                            <span class="name">Chris Fox</span>
                                            <span class="message">Designer, Blogger</span>
                                            <span class="badge badge-outline status"></span>
                                        </div>
                                    </div>
                                </a>                            
                            </li>
                            <li class="online">
                                <a href="javascript:void(0);">
                                    <div class="media">
                                        <img class="media-object " src="assets/images/xs/avatar5.jpg" alt="">
                                        <div class="media-body">
                                            <span class="name">Joge Lucky</span>
                                            <span class="message">Java Developer</span>
                                            <span class="badge badge-outline status"></span>
                                        </div>
                                    </div>
                                </a>                            
                            </li>
                            <li class="offline">
                                <a href="javascript:void(0);">
                                    <div class="media">
                                        <img class="media-object " src="assets/images/xs/avatar2.jpg" alt="">
                                        <div class="media-body">
                                            <span class="name">Isabella</span>
                                            <span class="message">CEO, Thememakker</span>
                                            <span class="badge badge-outline status"></span>
                                        </div>
                                    </div>
                                </a>                            
                            </li>
                            <li class="offline">
                                <a href="javascript:void(0);">
                                    <div class="media">
                                        <img class="media-object " src="assets/images/xs/avatar1.jpg" alt="">
                                        <div class="media-body">
                                            <span class="name">Folisise Chosielie</span>
                                            <span class="message">Art director, Movie Cut</span>
                                            <span class="badge badge-outline status"></span>
                                        </div>
                                    </div>
                                </a>                            
                            </li>
                            <li class="online">
                                <a href="javascript:void(0);">
                                    <div class="media">
                                        <img class="media-object " src="assets/images/xs/avatar3.jpg" alt="">
                                        <div class="media-body">
                                            <span class="name">Alexander</span>
                                            <span class="message">Writter, Mag Editor</span>
                                            <span class="badge badge-outline status"></span>
                                        </div>
                                    </div>
                                </a>                            
                            </li>                        
                        </ul>
                    </div>                   
                </div>
            </div>
            <div class="col-md-12 col-lg-8">
                <div class="card">
                    <div class="header">
                        <h2><strong>Social</strong> Media</h2>
                        <ul class="header-dropdown">
                            <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more"></i> </a>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="javascript:void(0);">Action</a></li>
                                    <li><a href="javascript:void(0);">Another action</a></li>
                                    <li><a href="javascript:void(0);">Something else</a></li>
                                    <li><a href="javascript:void(0);" class="boxs-close">Delete</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div class="body">
                        <div class="table-responsive social_media_table">
                            <table class="table m-b-0 table-hover">
                                <tbody>
                                    <tr>
                                        <td><span class="social_icon twitter-table"><i class="zmdi zmdi-twitter"></i></span>
                                        </td>
                                        <td><span class="list-name">Twitter</span>
                                            <span class="text-muted">Arkansas, United States</span>
                                        </td>
                                        <td><i class="zmdi zmdi-thumb-up"></i> 7K</td>
                                        <td><i class="zmdi zmdi-comments"></i> 11K</td>                                        
                                        <td class="text-right">
                                            <span class="badge badge-success">952</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="social_icon facebook"><i class="zmdi zmdi-facebook"></i></span>
                                        </td>
                                        <td><span class="list-name">Facebook</span>
                                            <span class="text-muted">Illunois, United States</span>
                                        </td>
                                        <td><i class="zmdi zmdi-thumb-up"></i> 15K</td>
                                        <td><i class="zmdi zmdi-comments"></i> 18K</td>                                        
                                        <td class="text-right">
                                            <span class="badge badge-success">6127</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="social_icon google"><i class="zmdi zmdi-google-plus"></i></span>
                                        </td>
                                        <td><span class="list-name">Google Plus</span>
                                            <span class="text-muted">Arizona, United States</span>
                                        </td>
                                        <td><i class="zmdi zmdi-thumb-up"></i> 15K</td>
                                        <td><i class="zmdi zmdi-comments"></i> 18K</td>                                        
                                        <td class="text-right">
                                            <span class="badge badge-success">325</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="social_icon linkedin"><i class="zmdi zmdi-linkedin"></i></span>
                                        </td>
                                        <td><span class="list-name">Linked In</span>
                                            <span class="text-muted">Florida, United States</span>
                                        </td>
                                        <td><i class="zmdi zmdi-thumb-up"></i> 19K</td>
                                        <td><i class="zmdi zmdi-comments"></i> 14K</td>                                        
                                        <td class="text-right">
                                            <span class="badge badge-success">2341</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="social_icon youtube"><i class="zmdi zmdi-youtube-play"></i></span>
                                        </td>
                                        <td><span class="list-name">YouTube</span>
                                            <span class="text-muted">Alaska, United States</span>
                                        </td>
                                        <td><i class="zmdi zmdi-thumb-up"></i> 15K</td>
                                        <td><i class="zmdi zmdi-comments"></i> 18K</td>                                        
                                        <td class="text-right">
                                            <span class="badge badge-success">160</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>            
        </div>         
        <div class="row clearfix">
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="body widget-user">
                        <img class="rounded-circle" src="assets/images/sm/avatar1.jpg" alt="">
                        <div class="wid-u-info">
                            <h5>Chadengle</h5>
                            <p class="text-muted m-b-0">info@wraptheme.com</p>
                            <small class="text-warning"><b>Admin</b></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="body widget-user">
                        <img class="rounded-circle" src="assets/images/sm/avatar2.jpg" alt="">
                        <div class="wid-u-info">
                            <h5>Damien Ritz</h5>
                            <p class="text-muted m-b-0">info@example.com</p>
                            <small class="text-warning"><b>UI UUX Designer</b></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="body widget-user">
                        <img class="rounded-circle" src="assets/images/sm/avatar3.jpg" alt="">
                        <div class="wid-u-info">
                            <h5>Monica Ryther</h5>
                            <p class="text-muted m-b-0">info@example.com</p>
                            <small class="text-warning"><b>Developer</b></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row clearfix">
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card info-box-2 hover-zoom-effect facebook s-widget">
                    <div class="icon"><i class="zmdi zmdi-facebook"></i></div>
                    <div class="content">
                        <div class="text">Like</div>
                        <div class="number">123</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card info-box-2 hover-zoom-effect instagram s-widget">
                    <div class="icon"><i class="zmdi zmdi-instagram"></i></div>
                    <div class="content">
                        <div class="text">Followers</div>
                        <div class="number">231</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card info-box-2 hover-zoom-effect twitter s-widget">
                    <div class="icon"><i class="zmdi zmdi-twitter"></i></div>
                    <div class="content">
                        <div class="text">Followers</div>
                        <div class="number">31</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card info-box-2 hover-zoom-effect google s-widget">
                    <div class="icon"><i class="zmdi zmdi-google"></i></div>
                    <div class="content">
                        <div class="text">Like</div>
                        <div class="number">254</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card info-box-2 hover-zoom-effect linkedin s-widget">
                    <div class="icon"><i class="zmdi zmdi-linkedin"></i></div>
                    <div class="content">
                        <div class="text">Followers</div>
                        <div class="number">2510</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="card info-box-2 hover-zoom-effect behance s-widget">
                    <div class="icon"><i class="zmdi zmdi-behance"></i></div>
                    <div class="content">
                        <div class="text">Project</div>
                        <div class="number">121</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row clearfix">
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="carousel slide twitter feed" data-ride="carousel">
                        <div class="carousel-inner" role="listbox">
                            <div class="carousel-item active">
                                <i class="zmdi zmdi-twitter zmdi-hc-2x"></i>
                                <p>23th Feb</p>
                                <h4>Now Get <span>Up to 70% Off</span><br>on buy</h4>
                                <div class="m-t-20"><i>- post form ThemeMakker</i></div>
                            </div>
                            <div class="carousel-item">
                                <i class="zmdi zmdi-twitter zmdi-hc-2x"></i>
                                <p>25th Jan</p>
                                <h4>Now Get <span>50% Off</span><br>on buy</h4>
                                <div class="m-t-20"><i>- post form WrapTheme</i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="carousel slide google feed" data-ride="carousel">
                        <div class="carousel-inner" role="listbox">
                            <div class="carousel-item active">
                                <i class="zmdi zmdi-google-plus zmdi-hc-2x"></i>
                                <p>18th Feb</p>
                                <h4>Now Get <span>Up to 70% Off</span><br>on buy</h4>
                                <div class="m-t-20"><i>- post form WrapTheme</i></div>
                            </div>
                            <div class="carousel-item">
                                <i class="zmdi zmdi-google-plus zmdi-hc-2x"></i>
                                <p>28th Mar</p>
                                <h4>Now Get <span>50% Off</span><br>on buy</h4>
                                <div class="m-t-20"><i>- post form ThemeMakker</i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="carousel slide facebook feed" data-ride="carousel">
                        <div class="carousel-inner" role="listbox">
                            <div class="carousel-item active">
                                <i class="zmdi zmdi-facebook zmdi-hc-2x"></i>
                                <p>20th Jan</p>
                                <h4>Now Get <span>50% Off</span><br>on buy</h4>
                                <div class="m-t-20"><i>- post form Theme</i></div>
                            </div>
                            <div class="carousel-item">
                                <i class="zmdi zmdi-facebook zmdi-hc-2x"></i>
                                <p>23th Feb</p>
                                <h4>Now Get <span>Up to 70% Off</span><br>on buy</h4>
                                <div class="m-t-20"><i>- post form Theme</i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>        
        <div class="row clearfix">
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="body widget-user">
                        <img class="rounded-circle" src="assets/images/sm/avatar4.jpg" alt="">
                        <div class="wid-u-info">
                            <h5>Fidel Tonn</h5>
                            <p class="text-muted m-b-0">123 6th St. Melbourne, <br>FL 32904</p>                            
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-4">
                                <h5 class="m-b-0">27</h5>
                                <small>Files</small>
                            </div>
                            <div class="col-4">
                                <h5 class="m-b-0">3.1GB</h5>
                                <small>Used</small>
                            </div>
                            <div class="col-4">
                                <h5 class="m-b-0">$ 908</h5>
                                <small>Spent</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="body widget-user">
                        <img class="rounded-circle" src="assets/images/sm/avatar5.jpg" alt="">
                        <div class="wid-u-info">
                            <h5>Gary Camara</h5>
                            <p class="text-muted m-b-0">70 Bowman St. South Windsor,<br> CT 06074</p>                            
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-4">
                                <h5 class="m-b-0">22</h5>
                                <small>Files</small>
                            </div>
                            <div class="col-4">
                                <h5 class="m-b-0">2.3GB</h5>
                                <small>Used</small>
                            </div>
                            <div class="col-4">
                                <h5 class="m-b-0">$ 656</h5>
                                <small>Spent</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="body widget-user">
                        <img class="rounded-circle" src="assets/images/sm/avatar6.jpg" alt="">
                        <div class="wid-u-info">
                            <h5>Tim Hank</h5>
                            <p class="text-muted m-b-0">795 Folsom Ave, Suite 600<br> San Francisco</p>                            
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-4">
                                <h5 class="m-b-0">16</h5>
                                <small>Files</small>
                            </div>
                            <div class="col-4">
                                <h5 class="m-b-0">1.8GB</h5>
                                <small>Used</small>
                            </div>
                            <div class="col-4">
                                <h5 class="m-b-0">$ 215</h5>
                                <small>Spent</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row clearfix">
            <div class="col-md-12 col-lg-4">
                <div class="card weather">
                     <div class="header">
                        <h2><strong>Weather</strong></h2>
                    </div>
                    <div class="body">
                        <div class="city">
                            <span>sky is clear</span>
                            <h3>New York</h3>
                            <img src="assets/images/weather/summer.svg" alt="">
                        </div>
                        <ul class="row days list-unstyled m-b-0">
                            <li>
                                <h5>SUN</h5>
                                <img src="assets/images/weather/sky.svg" alt="">
                                <span class="degrees">77</span>
                            </li>
                            <li>
                                <h5>MON</h5>
                                <img src="assets/images/weather/rain.svg" alt="">
                                <span class="degrees">81</span>
                            </li>
                            <li>
                                <h5>TUE</h5>
                                <img src="assets/images/weather/summer.svg" alt="">
                                <span class="degrees">82</span>
                            </li>
                            <li>
                                <h5>WED</h5>
                                <img src="assets/images/weather/summer.svg" alt="">
                                <span class="degrees">82</span>
                            </li>
                            <li>
                                <h5>THU</h5>
                                <img src="assets/images/weather/cloudy.svg" alt="">
                                <span class="degrees">81</span>
                            </li>
                            <li>
                                <h5>FRI</h5>
                                <img src="assets/images/weather/summer.svg" alt="">
                                <span class="degrees">67</span>
                            </li>
                            <li>
                                <h5>SAT</h5>
                                <img src="assets/images/weather/cloudy.svg" alt="">
                                <span class="degrees">81</span>
                            </li>
                        </ul>						
                    </div>
                </div>
                <div class="card">
                    <div class="header">
                        <h2><strong>28</strong> Mar 2018</h2>
                        <ul class="header-dropdown">
                            <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more"></i> </a>
                                <ul class="dropdown-menu slideUp">
                                    <li><a href="javascript:void(0);">Action</a></li>
                                    <li><a href="javascript:void(0);">Another action</a></li>
                                    <li><a href="javascript:void(0);">Something else</a></li>
                                    <li><a href="javascript:void(0);" class="boxs-close">Deletee</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>                    
                    <div class="body w_calender days">
                        <ul class="m-t-10">
                            <li>MON</li>
                            <li>TUE</li>
                            <li>WED</li>
                            <li>THU</li>
                            <li>FRI</li>
                            <li>SAT</li>
                            <li>SUN</li>
                        </ul>                                
                        <ul>
                            <li>1</li>
                            <li>2</li>
                            <li>3</li>
                            <li>4</li>
                            <li>5</li>
                            <li>6</li>
                            <li>7</li>
                        </ul>                                
                        <ul>
                            <li>8</li>
                            <li>9</li>
                            <li>10</li>
                            <li>11</li>
                            <li>12</li>
                            <li>13</li>
                            <li>14</li>
                        </ul>                                
                        <ul>
                            <li>15</li>
                            <li>16</li>
                            <li>17</li>
                            <li>18</li>
                            <li>19</li>
                            <li>20</li>
                            <li>21</li>
                        </ul>                                
                        <ul>
                            <li>22</li>
                            <li>23</li>
                            <li>24</li>
                            <li>25</li>
                            <li>26</li>
                            <li>27</li>
                            <li><em>28</em></li>
                        </ul>                                
                        <ul>
                            <li>29</li>
                            <li>30</li>
                            <li>31</li>
                            <li>1</li>
                            <li>2</li>
                            <li>3</li>
                            <li>4</li>
                        </ul>                                
                    </div>
                </div>
                <div class="card">
                    <div class="header">
                        <h2><strong>Subscribe</strong></h2>
                        <ul class="header-dropdown">
                            <li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"> <i class="zmdi zmdi-more"></i> </a>
                                <ul class="dropdown-menu slideUp">
                                    <li><a href="javascript:void(0);">Action</a></li>
                                    <li><a href="javascript:void(0);">Another action</a></li>
                                    <li><a href="javascript:void(0);">Something else</a></li>
                                    <li><a href="javascript:void(0);" class="boxs-close">Deletee</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>                    
                    <div class="body">
                        <form>
                            <div class="form-group">
                                <input type="text" value="" placeholder="Enter Name" class="form-control">
                            </div>
                            <div class="form-group">
                                <input type="text" value="" placeholder="Enter Email" class="form-control">
                            </div>
                            <button class="btn btn-primary btn-round">Subscribe</button>
                        </form>         
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-lg-8">
                <div class="card">
                    <div class="row profile_state">
                        <div class="col-lg-3 col-md-3 col-6">
                            <div class="body">
                                <i class="zmdi zmdi-eye zmdi-hc-2x col-amber"></i>
                                <h4 class="m-b-0">2,365</h4>
                                <span>Post View</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-3 col-6">
                            <div class="body">
                                <i class="zmdi zmdi-thumb-up zmdi-hc-2x col-blue"></i>
                                <h4 class="m-b-0">365</h4>
                                <span>Likes</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-3 col-6">
                            <div class="body">
                                <i class="zmdi zmdi-comment-text zmdi-hc-2x col-red"></i>
                                <h4 class="m-b-0">65</h4>
                                <span>Comments</span>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-3 col-6">
                            <div class="body">
                                <i class="zmdi zmdi-account zmdi-hc-2x text-success"></i>
                                <h4 class="m-b-0">2,055</h4>
                                <span>Profile Views</span>
                            </div>
                        </div>                      
                    </div>
                </div>
                <div class="card">
                    <div class="body">
                        <div id="demo2" class="carousel slide" data-ride="carousel">
                            <!-- Indicators -->
                            <ul class="carousel-indicators">
                                <li data-target="#demo2" data-slide-to="0" class="active"></li>
                                <li data-target="#demo2" data-slide-to="1" class=""></li>
                                <li data-target="#demo2" data-slide-to="2" class=""></li>
                            </ul>            
                            <!-- Wrapper for slides -->
                            <div class="carousel-inner">
                                <div class="carousel-item active">
                                    <img src="assets/images/image-gallery/5.jpg" class="img-fluid" alt="">
                                    <div class="carousel-caption">
                                        <h3>Chicago</h3>
                                        <p>Thank you, Chicago!</p>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="assets/images/image-gallery/6.jpg" class="img-fluid" alt="">
                                    <div class="carousel-caption">
                                        <h3>New York</h3>
                                        <p>We love the Big Apple!</p>
                                    </div>
                                </div>
                                <div class="carousel-item">
                                    <img src="assets/images/image-gallery/12.jpg" class="img-fluid" alt="">
                                    <div class="carousel-caption">
                                        <h3>Los Angeles</h3>
                                        <p>We had such a great time in LA!</p>
                                    </div>
                                </div>
                            </div>            
                            <!-- Controls -->
                            <!-- Left and right controls -->
                            <a class="carousel-control-prev" href="#demo2" data-slide="prev"><span class="carousel-control-prev-icon"></span></a>
                            <a class="carousel-control-next" href="#demo2" data-slide="next"><span class="carousel-control-next-icon"></span></a>
                        </div>
                    </div>
                </div>                
            </div>            
        </div>        
    </div>
</section>

<?php require "layouts/vendor-scripts.php"; ?>
<?php require "layouts/foot.php"; ?>
