<?php
ob_start();

session_start();

include('layout/header.php');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "GET") :

    $secure_id = $_GET['q'];
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
                    <h4 class="m-0 font-weight-bold text-primary">Add Chapter</h4>
                </div>
                <div class="card-body">
                    <form class="px-5 form-upsert" action="api/create_chapter.php" method="post" enctype="multipart/form-data">
                        <button type="button" class="btn btn-success my-2" onclick="addChapter()">Add Chapter</button>
                        <div class="scrollable-container">
                            <div class="form-row">
                                <div class="form-group col-12 ">
                                    <div id="chapterContainer">
                                    </div>
                                </div>
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

            <div class="card shadow mb-4 mx-5">
                <div class="card-header py-3">
                    <h4 class="m-0 font-weight-bold text-primary">Edit Chapter</h4>
                </div>
                <div class="card-body">
                    <div class="form-group col-12">
                        <div class="form-row px-5">
                            Choose chapter
                            <select required name="chapter_list" id="chapter_list" class="form-control form-control-sm">
                                <option disabled selected value> Select Chapters ..</option>
                                <?php
                                foreach($chapters as $chapter) {
                                    echo '<option value="'.$chapter['secure_id'].'"';
                                    echo '>'.$chapter['name'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <br>
                        <div class="form-row px-5">
                            <?php
                            foreach($chapters as $chapter) {
                                echo '<form class="form-upsert" action="api/update_chapter.php?q='.$chapter['secure_id'].'" method="post" enctype="multipart/form-data">';
                                echo '<div id="chapter_' . $chapter['secure_id'] . '" class="chapter-content" style="display: none;">';
                                echo '<label>Chapter Name</label>';
                                echo '<input class="form-control form-control-md" type="text" name="chapter_title" required value="'.$chapter['name'].'"/>';
                                echo '<label>Images (Upload to change) :</label>';
                                echo '<label style="color:green;">&nbsp;	&nbsp;Exist : '.$chapter['amount'].' images</label>';
                                echo '<input class="form-control form-control-md" type="file" name="chapter_image[]" multiple accept="image/*"/>';
                                echo '<input type="hidden" name="secure_id" value="'.$chapter['secure_id'].'">';
                                echo '<input type="submit" class="btn btn-success my-3" value="Save Chapter"> ';
                                echo '<button type="button" class="btn btn-danger my-3" id="delete-btn" data-secure-id="'.$chapter['secure_id'].'">Delete Chapter</button>';
                                echo '</div>';
                                if (isset($_SESSION['error'])) : ?>
                                    <p style="color: red;"><?php echo $_SESSION['error']; ?></p>
                                <?php endif; ?>
                                <?php
                                unset($_SESSION['error']); 
                                echo '</form>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete this chapter?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <a href="#" id="confirmDelete" class="btn btn-danger">Delete</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->

    </div>
    <!-- End of Main Content -->

    <?php include 'layout/footer.php'?>

    </div>
    <!-- End of Content Wrapper -->

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

        let chapterCount = <?php echo isset($chapterCount) ? $chapterCount : 0; ?>;

        function addChapter() {
            chapterCount++;
            const container = document.getElementById('chapterContainer');
            const chapter = document.createElement('div');
            chapter.className = 'chapter-container';
            chapter.innerHTML = `
                <label>Chapter</label>
                <input class="form-control form-control-sm" type="text" name="chapters[${chapterCount}][title]" required />
                <label>Images:</label>
                <input class="form-control form-control-md" type="file" name="chapters[${chapterCount}][file][]" multiple accept="image/*"/>
                <button type="button" class="btn btn-danger my-2" onclick="removeChapter(this)">Remove</button>
                <hr>
            `;
            container.appendChild(chapter);     
        }

        function removeChapter(button) {
            const container = document.getElementById('chapterContainer');
            container.removeChild(button.parentNode);
        }

        $(document).ready(function() {
            $('#chapter_list').on('change', function() {
                // Hide all chapter-content divs
                $('.chapter-content').hide();

                // Get the selected secure_id
                var selectedSecureId = $(this).val();
                
                // Show the corresponding chapter-content div
                if (selectedSecureId) {
                    $('#chapter_' + selectedSecureId).show();
                }
            });
        });
        
        $('#delete-btn').click(function(event) {
            event.preventDefault();
            var secureId = $(this).data('secure-id');
            var deleteUrl = 'api/delete_chapter.php?q=' + secureId;
            $('#confirmDelete').attr('href', deleteUrl);
            $('#deleteModal').modal('show');
        });
    </script>


</body>

</html>
<?php endif;?>