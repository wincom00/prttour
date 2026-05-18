<?php
    // check if post data is available or not

    if (isset($_POST['fileName']) && $_POST['fileData']){
        // save uploaded file
        $uploadDir = '';
        file_put_contents(
            $uploadDir. $_POST['fileName'],
            base64_decode($_POST['fileData'])
        );

        echo "Success";

   } else {
      echo "파일이 업로드 되지 않았습니다.";
   }


?>