<?php
include 'header.php';
if (!empty($_SESSION['current_user'])) {
    ?>
<div class="main-content">
    <h1><?= !empty($_GET['id']) ? ((!empty($_GET['task']) && $_GET['task'] == "copy") ? "Copy truyện" : "Sửa truyện") : "Thêm truyện" ?>
    </h1>
    <div id="content-box">
        <?php
            if (isset($_GET['action']) && ($_GET['action'] == 'add' || $_GET['action'] == 'edit')) {
                if (isset($_POST['name']) && !empty($_POST['name']) && isset($_POST['chapter']) && !empty($_POST['chapter'])) {
                    $galleryImages = array();
                    if (empty($_POST['name'])) {
                        $error = "Bạn phải nhập tên truyện";
                    } elseif (empty($_POST['chapter'])) {
                        $error = "Bạn phải nhập Chapter truyện";
                    } elseif (!empty($_POST['chapter']) && is_numeric(str_replace('.', '', $_POST['chapter'])) == false) {
                        $error = "Chapter nhập không hợp lệ";
                    }
                    if (isset($_FILES['image']) && !empty($_FILES['image']['name'][0])) {
                        $uploadedFiles = $_FILES['image'];
                        $result = uploadFiles($uploadedFiles);
                        if (!empty($result['errors'])) {
                            $error = $result['errors'];
                        } else {
                            $image = $result['path'];
                        }
                    }
                    if (!isset($image) && !empty($_POST['image'])) {
                        $image = $_POST['image'];
                    }
                    if (isset($_FILES['gallery']) && !empty($_FILES['gallery']['name'][0])) {
                        $uploadedFiles = $_FILES['gallery'];
                        $result = uploadFiles($uploadedFiles);
                        if (!empty($result['errors'])) {
                            $error = $result['errors'];
                        } else {
                            $galleryImages = $result['uploaded_files'];
                        }
                    }
                    if (!empty($_POST['gallery_image'])) {
                        $galleryImages = array_merge($galleryImages, $_POST['gallery_image']);
                    }
                    if (!isset($error)) {
                        if ($_GET['action'] == 'edit' && !empty($_GET['id'])) { //Cập nhật lại truyện
                            $result = mysqli_query($con, "UPDATE `product` SET `name` = '" . $_POST['name'] . "',`image` =  '" . $image . "', `chapter` = " . str_replace('.', '', $_POST['chapter']) . ", `content` = '" . $_POST['content'] . "', `last_updated` = " . time() . " WHERE `product`.`id` = " . $_GET['id']);
                        } else { //Thêm truyện
                            $result = mysqli_query($con, "INSERT INTO `product` (`id`, `name`, `image`, `chapter`, `content`, `created_time`, `last_updated`) VALUES (NULL, '" . $_POST['name'] . "','" . $image . "', " . str_replace('.', '', $_POST['chapter']) . ", '" . $_POST['content'] . "', " . time() . ", " . time() . ");");
                        }
                        if (!$result) { //Nếu có lỗi xảy ra
                            $error = "Có lỗi xảy ra trong quá trình thực hiện.";
                        } else { //Nếu thành công
                            if (!empty($galleryImages)) {
                                $product_id = ($_GET['action'] == 'edit' && !empty($_GET['id'])) ? $_GET['id'] : $con->insert_id;
                                $insertValues = "";
                                foreach ($galleryImages as $path) {
                                    if (empty($insertValues)) {
                                        $insertValues = "(NULL, " . $product_id . ", '" . $path . "', " . time() . ", " . time() . ")";
                                    } else {
                                        $insertValues .= ",(NULL, " . $product_id . ", '" . $path . "', " . time() . ", " . time() . ")";
                                    }
                                }
                                $result = mysqli_query($con, "INSERT INTO `image_library` (`id`, `product_id`, `path`, `created_time`, `last_updated`) VALUES " . $insertValues . ";");
                            }
                        }
                    }
                } else {
                    $error = "Bạn chưa nhập thông tin truyện.";
                }
                ?>
        <div class="container">
            <div class="error"><?= isset($error) ? $error : "Cập nhật thành công" ?></div>
            <a href="product_listing.php">Quay lại danh sách truyện</a>
        </div>
        <?php
            } else {
                if (!empty($_GET['id'])) {
                    $result = mysqli_query($con, "SELECT * FROM `product` WHERE `id` = " . $_GET['id']);
                    $product = $result->fetch_assoc();
                    $gallery = mysqli_query($con, "SELECT * FROM `image_library` WHERE `product_id` = " . $_GET['id']);
                    if (!empty($gallery) && !empty($gallery->num_rows)) {
                        while ($row = mysqli_fetch_array($gallery)) {
                            $product['gallery'][] = array(
                                'id' => $row['id'],
                                'path' => $row['path']
                            );
                        }
                    }
                }
                ?>
        <form id="product-form" method="POST"
            action="<?= (!empty($product) && !isset($_GET['task'])) ? "?action=edit&id=" . $_GET['id'] : "?action=add" ?>"
            enctype="multipart/form-data">
            <input type="submit" title="Lưu truyện" value="" />
            <div class="clear-both"></div>
            <div class="wrap-field">
                <label>Tên truyện: </label>
                <input type="text" name="name" value="<?= (!empty($product) ? $product['name'] : "") ?>" />
                <div class="clear-both"></div>
            </div>
            <div class="wrap-field">
                <label>Chapter truyện: </label>
                <input type="text" name="chapter"
                    value="<?= (!empty($product) ? number_format($product['chapter'], 0, ",", ".") : "") ?>" />
                <div class="clear-both"></div>
            </div>
            <div class="wrap-field">
                <label>Ảnh đại diện: </label>
                <div class="right-wrap-field">
                    <?php if (!empty($product['image'])) { ?>
                    <img src="../<?= $product['image'] ?>" /><br />
                    <input type="hidden" name="image" value="<?= $product['image'] ?>" />
                    <?php } ?>
                    <input type="file" name="image" />
                </div>
                <div class="clear-both"></div>
            </div>
            <div class="wrap-field">
                <label>Thư viện ảnh: </label>
                <div class="right-wrap-field">
                    <?php if (!empty($product['gallery'])) { ?>
                    <ul>
                        <?php foreach ($product['gallery'] as $image) { ?>
                        <li>
                            <img src="../<?= $image['path'] ?>" />
                            <a href="gallery_delete?id=<?= $image['id'] ?>">Xóa</a>
                        </li>
                        <?php } ?>
                    </ul>
                    <?php } ?>
                    <?php if (isset($_GET['task']) && !empty($product['gallery'])) { ?>
                    <?php foreach ($product['gallery'] as $image) { ?>
                    <input type="hidden" name="gallery_image[]" value="<?= $image['path'] ?>" />
                    <?php } ?>
                    <?php } ?>
                    <input multiple="" type="file" name="gallery[]" />
                </div>
                <div class="clear-both"></div>
            </div>
            <div class="wrap-field">
                <label>Nội dung: </label>
                <textarea name="content"
                    id="product-content"><?= (!empty($product) ? $product['content'] : "") ?></textarea>
                <div class="clear-both"></div>
            </div>
        </form>
        <div class="clear-both"></div>
        <script>
        // Replace the <textarea id="editor1"> with a CKEditor
        // instance, using default configuration.
        CKEDITOR.replace('product-content');
        </script>
        <?php } ?>
    </div>
</div>

<?php
}
include './footer.php';
?>