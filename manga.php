<?php
ob_start();

session_start();

include('layout/header.php');

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
}

$path = 'db.sqlite';
require 'api/get_manga.php'
?>

<body id="page-top">

    <?php
     include 'layout/sidebar.php';
    ?>
    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

    <!-- Main Content -->
    <div id="content">

        <?php include 'layout/top_bar.php'?>

        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- DataTales Example -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h4 class="m-0 font-weight-bold text-primary">Manga</h4>
                </div>
                <div class="card-body">
                    <a type="button" class="btn btn-success my-2" href="upsert_manga.php">Create Manga</a>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Status</th>
                                    <th>Genre</th>
                                    <th>Chapters</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($mangas as $manga): ?> 
                                    <tr>
                                        <td><?php echo htmlspecialchars($manga['title']); ?></td>
                                        <td><?php echo htmlspecialchars($manga['author']); ?></td>
                                        <td><?php echo htmlspecialchars($manga['status']); ?></td>
                                        <td><?php echo htmlspecialchars($manga['genre']);?></td>
                                        <td><?php echo htmlspecialchars($manga['chapters']);?></td>
                                        <td>
                                            <?php echo '<a href="upsert_manga.php?q='.htmlspecialchars($manga['secure_id']).'"><i class="fas fa-lg fa-edit"></i></a>';?>
                                            <span style="margin-left:20px;"></span>
                                            <?php echo '<a href="#" class="delete-btn" data-secure-id="'.$manga['secure_id'].'"><i class="fas fa-lg fa-trash"></i></a>';?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
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
                        Are you sure you want to delete this manga?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <a href="#" id="confirmDelete" class="btn btn-danger">Delete</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Main Content -->

    <?php include 'layout/footer.php'?>

    </div>
    <!-- End of Content Wrapper -->

    </div>
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
    <script>
        $(document).ready(function() {
        $('.delete-btn').click(function(event) {
            event.preventDefault();
            var secureId = $(this).data('secure-id');
            var deleteUrl = 'api/delete_manga.php?q=' + secureId;
            $('#confirmDelete').attr('href', deleteUrl);
            $('#deleteModal').modal('show');
        });
    });
    </script>
</body>

</html>