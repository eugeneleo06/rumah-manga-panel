<?php

session_start();

include('layout/header.php');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
}

if ($_SERVER["REQUEST_METHOD"] == "GET") :

    $title = "Create";
    if (isset($_GET['q'])) {
        $title = "Edit";
        $secure_id = $_GET['q'];
    }
    $path = 'db.sqlite';
    require 'api/detail_manga.php';
?>

<body id="page-top">

    <?php
     include 'layout/sidebar.php'
    ?>
    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

    <!-- Main Content -->
    <div id="content">

        <?php include 'layout/top_bar.php'?>

        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- DataTables Example -->
            <div class="card shadow mb-4 mx-5">
                <div class="card-header py-3">
                    <h4 class="m-0 font-weight-bold text-primary"><?php echo $title?> Manga</h4>
                </div>
                <div class="card-body">
                    <form class="px-5 form-upsert" action="api/upsert_manga.php" method="post" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group col-6">
                                Title
                                <input required type="text" class="form-control form-control-sm" name="title" value="<?php if(isset($manga['title'])){echo $manga['title'];}?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-6">
                                Author
                                <select required name="author" class="form-control form-control-sm">
                                    <option disabled selected value> Select Author ..</option>
                                    <?php
                                    foreach($authors as $author) {
                                        echo '<option value="'.$author['id'].'"';
                                        if (isset($manga) && $author['id'] == $manga['author_id']) {
                                            echo ' selected';
                                        }
                                        echo '>'.$author['name'].'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-6">
                                Genre
                                <select required id="multi-select" name="genres[]" multiple="multiple" class="form-control form-control-sm" style="width:100%" required>
                                    <?php
                                    foreach($genres as $genre) {
                                        echo '<option value="'.$genre['id'].'"';
                                        if (isset($genreIds)){
                                            foreach($genreIds as $genreId) {
                                                if ($genre['id'] == $genreId) {
                                                    echo ' selected';
                                                }
                                            }
                                        }
                                        echo '>'.$genre['name'].'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-6">
                                Status
                                <select required name="status" class="form-control form-control-sm">
                                    <option disabled selected value> Select Status ..</option>
                                    <?php
                                    echo '<option value="ONGOING"';
                                    if (isset($manga) && $manga['status'] == "ONGOING") {
                                        echo ' selected';
                                    }
                                    echo '>Ongoing</option>';
                                    echo '<option value="COMPLETED"';
                                    if (isset($manga) && $manga['status'] == "COMPLETED") {
                                        echo ' selected';
                                    }
                                    echo '>Completed</option>';
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-12">
                                Synopsis
                                <textarea required name="synopsis" class="form-control" rows="3"><?php
                                        if (isset($manga['synopsis'])) {
                                            echo $manga['synopsis'];
                                        }
                                    ?></textarea>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-12">
                                Cover Image
                                <input class="form-control form-control-md" type="file" name="cover_image" accept="image/*"/>
                                <?php
                                    if(isset($manga['cover_img'])) {
                                        echo '<img class="img-thumbnail mt-2" src="'.$manga['cover_img'].'">';
                                    }
                                ?>
                            </div>
                        </div>
                        <input type="hidden" name="secure_id" value='<?php if(isset($secure_id)) {echo $secure_id;} ?>'>
                        <?php if (isset($_SESSION['error'])) : ?>
                            <p style="color: red;"><?php echo $_SESSION['error']; ?></p>
                        <?php endif; ?>
                        <?php
                        unset($_SESSION['error']); 
                        ?>
                        <div class="form-row mt-3">
                            <div class="form-group col-12">
                                <input type="submit" class="btn btn-success w-100" value="SAVE">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->

    </div>
    <!-- End of Main Content -->

    <?php include 'layout/footer.php'?>

    </div>
    <!-- End of Content Wrapper -->

    </>
    <!-- End of Page Wrapper -->
    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
    <!-- Page level plugins -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="js/demo/datatables-demo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#multi-select').select2({
                placeholder: "Select genre",
                allowClear: true
            });
        });
    </script>


</body>

</html>
<?php endif;?>