<div>
    <?php
    include 'functions/pagination.php';
        echo showAlert();
    ?>
    <p class="m-0 p-0" align="right">
        <button type="button" class="btn mb-2 btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
            [+] Add User
        </button>
    </p>
    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Tambah User</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="actions/?hal=users_user-management" method="post" enctype="multipart/form-data" class="ajax-form">
                    <div class="modal-body">
                        <label for="">Nama Lengkap</label>
                        <input type="text" name="fullname" class="form-control form-control-sm mb-2" placeholder="Nama Lengkap" id="">
                        <label for="">Username</label>
                        <input type="text" name="username" class="form-control form-control-sm mb-2" placeholder="Username" id="">
                        <label for="">Email</label>
                        <input type="email" name="email" class="form-control form-control-sm mb-2" placeholder="Email" id="">
                        <label for="">Password</label>
                        <input type="password" name="password" class="form-control form-control-sm mb-2" placeholder="Password" id="">
                        <label for="">Foto Profile</label>
                        <input type="file" name="photo_profile" class="form-control form-control-sm" id="">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="addUser" class="btn btn-sm btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card shadow p-2 mb-1">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm datatable" id="table1" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th>No </th>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Foto Profile</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $query = "SELECT * FROM users ORDER BY id DESC";
                    $result = querySecure($con, $query, [], '');
                    foreach($result as $data) {
                    ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $data['fullname']; ?></td>
                            <td><?php echo $data['username']; ?></td>
                            <td><?php echo $data['email']; ?></td>
                            <td>
                                <?php if (isset($data['photo_profile']) && !empty($data['photo_profile'])) { ?>
                                    <img src="<?php echo $data['photo_profile']; ?>" alt="Foto Profile" width="100">
                                <?php } else { ?>
                                    -
                                <?php } ?>
                            </td>
                            <td>
                                <button ctype="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal" onclick="upData('<?= $data['id'] ?>', '<?= $data['fullname'] ?>', '<?= $data['username'] ?>', '<?= $data['email'] ?>', '<?= $data['password'] ?>')"><i class="bi bi-pencil"></i></button>
                                <?php if($data['id'] != $_SESSION['admin']['id']){?>
                                    <a href="actions/?hal=users_user-management&deleteUser=<?= $data['id'] ?>" class="btn btn-sm btn-danger delete-btn"><i class="bi bi-trash"></i></a>
                                <?php }?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Modal Edit -->
    <script>
        function upData(id, fullname, username, email, password) {
            document.getElementById('id_id').value = id;
            document.getElementById('fullname_id').value = fullname;
            document.getElementById('username_id').value = username;
            document.getElementById('email_id').value = email;
            document.getElementById('password_id').value = password;
        }
    </script>
    <div class="modal fade" id="editModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Modal title</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="actions/?hal=users_user-management" method="post" enctype="multipart/form-data" class="ajax-form">
                    <div class="modal-body">
                        <input type="text" name="id" id="id_id" hidden>
                        <label for="">Nama Lengkap</label>
                        <input type="text" name="fullname" class="form-control form-control-sm mb-2" placeholder="Nama Lengkap" id="fullname_id">
                        <label for="">Username</label>
                        <input type="text" name="username" class="form-control form-control-sm mb-2" placeholder="Username" id="username_id">
                        <label for="">Email</label>
                        <input type="email" name="email" class="form-control form-control-sm mb-2" placeholder="Email" id="email_id">
                        <label for="">Password</label>
                        <input type="password" name="password" class="form-control form-control-sm mb-2" placeholder="Jangan diisi jika password tetap">
                        <input type="text" name="password_old" class="form-control form-control-sm mb-2" placeholder="Password" id="password_id" hidden>
                        <label for="">Foto Profile</label>
                        <input type="file" name="photo_profile" class="form-control form-control-sm">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="updateUser" class="btn btn-sm btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>