<?php
if (defined('SPA_LAYOUT')) {
    return;
}
?>
<script src="assets/bundles/libscripts.bundle.js"></script>



<!-- Flatpickr Plugin Js -->
<?php if ($page == 'profile' || $page == 'tarihe-gore-rapor-ara') { ?>

<?php } ?>

<!-- Datatable.js -->
<?php
if ($page == 'excelden-yukle') {
    echo '<script lang="javascript" src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.mini.min.js"></script>';
}

?>


<!-- Jquery Core Js -->
<script src="assets/bundles/vendorscripts.bundle.js"></script>
<script src="assets/bundles/mainscripts.bundle.js"></script>


<script src="
https://cdn.jsdelivr.net/npm/sweetalert2@11.22.4/dist/sweetalert2.all.min.js
"></script>
<script src="assets/plugins/multi-select/js/jquery.multi-select.js"></script> <!-- Multi Select Plugin Js -->

<script src="assets/plugins/jquery-validation/jquery.validate.js"></script> <!-- Jquery Validation Plugin Css -->
<script src="assets/js/script.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>



<script src="App/Src/button-loading.js"></script>

<script>
    $(function() {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>