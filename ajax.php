<?php



if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    header("Location: 404.php");
    exit();
}





include_once 'DatabaseConn/databaseConn.php';
include_once 'functions.php';
session_start();
date_default_timezone_set('Asia/Manila');
function decrypt_data($data, $key) {
    $cipher = "aes-256-cbc";
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
}

function encrypt_data($data, $key) {
    $cipher = "aes-256-cbc";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $encrypted = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($encrypted . '::' . $iv);
}
$secret_key = 'TheSecretKey#02';
$action = $_GET['action'];
extract($_POST);
if ($action== 'signUp'){

    echo 1;
}

if ($action == 'login') {

    $log_email = isset($_POST['log_email']) ? sanitizeInput($_POST['log_email']) : '';
    $log_password = $_POST['log_password'] ?? '';



    if ($log_email!== '' && $log_password !== '') {

        $fetch_acc = "SELECT user_id, password FROM tbl_accounts WHERE email = ?";
        $stmt = $conn->prepare($fetch_acc);
        $stmt->bind_param("s", $log_email);
        $stmt->execute();



        if ($stmt->error) {
            echo "Error executing statement: " . $stmt->error;
            exit;
        }

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $user_id = $row['user_id'];
            $hashed_password = $row['password'];
            if (password_verify($log_password, $hashed_password)) {
                $fetch_user_info = "SELECT user_type FROM tbl_user_info WHERE user_id = ?";
                $stmt_user_info = $conn->prepare($fetch_user_info);
                $stmt_user_info->bind_param('i', $user_id);
                $stmt_user_info->execute();

                if ($stmt_user_info->error) {
                    echo "Error executing statement: " . $stmt_user_info->error;
                    exit; // Stop execution
                }

                $result_user_info = $stmt_user_info->get_result();

                if ($result_user_info->num_rows == 1) {
                    $row_user_info = $result_user_info->fetch_assoc();
                    $_SESSION['log_user_id'] = $user_id;
                    $_SESSION['log_user_type'] = $row_user_info['user_type'];
                    echo 1; // Login successful
                } else {
                    echo 2; // Error: User type not found
                }
            } else {
                echo 2; // Error: Incorrect password
            }
        } else {
            echo 2; // Error: User not found
        }
    } else {
        echo 2; // Error: Email or password empty
    }


}




if ($action == 'addWeeklyReport'){

    echo $newWeeklyReport;
}
if ($action == 'resubmitReport'){

    echo $resubmitReport;

}
if ($action == 'newFinalReport'){

    $first_name = isset($_POST['first_name']) ? sanitizeInput($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? sanitizeInput($_POST['last_name']) : '';
    $program = isset($_POST['program']) ? sanitizeInput($_POST['program']) : '';
    $section = isset($_POST['section']) ? sanitizeInput($_POST['section']) : '';
    $stud_sex = isset($_POST['stud_Sex']) ? sanitizeInput($_POST['stud_Sex']) :'';
    $ojt_adviser = isset($_POST['ojt_adviser']) ? sanitizeInput($_POST['ojt_adviser']) : '';
    $school_id = isset($_POST['school_id']) && is_numeric($_POST['school_id']) && check_uniq_stud_id($_POST['school_id']) ? sanitizeInput($_POST['school_id']) : '';
    if ($first_name !== '' && $stud_sex !== '' && $last_name !== '' && $program !== '' && $section !== '' && $ojt_adviser !== '' && $school_id !== '') {
        if(isset($_FILES['final_report_file'])) {
            $file_name = $_FILES['final_report_file']['name'];
            $file_temp = $_FILES['final_report_file']['tmp_name'];
            $file_type = $_FILES['final_report_file']['type'];
            $file_error = $_FILES['final_report_file']['error'];
            $file_size = $_FILES['final_report_file']['size'];

            if (isPDF($file_name)){

                $file_first_name = str_replace(' ', '', $first_name);
                $file_last_name = str_replace(' ', '', $last_name);
                $new_file_name = $file_first_name."_".$file_last_name."_".$program."_".$section."_".$school_id.".pdf";
                $current_date_time = date('Y-m-d H:i:s');
                $narrative_status = 'OK';
                if($file_error === UPLOAD_ERR_OK) {

                    $new_final_report = $conn->prepare("INSERT INTO narrativereports (stud_school_id, sex,
                              first_name, last_name, program, section, OJT_adviser,narrative_file_name, upload_date, file_status)
                              values (?,?,?,?,?,?,?,?,?,?)");

                    $new_final_report->bind_param("ssssssssss",
                        $school_id,$stud_sex, $first_name, $last_name,
                        $program, $section, $ojt_adviser, $new_file_name,
                        $current_date_time, $narrative_status);


                    if (!$new_final_report->execute()){
                        echo 'query error';
                        exit();
                    }
                    $new_final_report->close();
                    $destination = "src/NarrativeReportsPDF/" . $new_file_name;
                    move_uploaded_file($file_temp, $destination);
                    $report_pdf_file_name = $file_first_name."_".$file_last_name."_".$program."_".$section."_".$school_id;

                    if (convert_pdf_to_image($report_pdf_file_name)){
                        echo 1;
                        exit();
                    }
                    else{
                        echo 'Flip book Conversion error';
                    }
                }
                else {
                    echo 'file error';
                }
            }else {
                echo 'not pdf';
            }
        }else{
            echo 'empty file';
        }
    }else{
        echo 'form data are empty';
    }
    exit();
}
if ($action == 'get_narrativeReports') {
    $sql = "SELECT * FROM narrativereports where file_status = 'OK' order by upload_date desc";
    $result = $conn->query($sql);
    $number = 1;
    if ($result === false) {
        echo "Error: " . $conn->error;
    } else {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (isset($_GET['homeTable']) && $_GET['homeTable'] == 'request') {
                    echo '<tr class="border-b border-dashed last:border-b-0 p-3">
                        <td class="p-3 text-start">
                            <span class="font-semibold text-light-inverse text-md/normal">' . $number++ . '</span>
                        </td>
                        <td class="p-3 text-start">
                            <span class="font-semibold text-light-inverse text-md/normal">' . $row['first_name'] . ' ' . $row['last_name'] . '</span>
                        </td>
                        <td class="p-3 text-end">
                            <span class="font-semibold text-light-inverse text-md/normal">' . $row["program"] . '</span>
                        </td>
                        <td class="p-3 text-end ">
                            <a href="flipbook.php?view=' . urlencode(encrypt_data($row['narrative_id'], $secret_key)) .'" target="_blank" class="hover:cursor-pointer mb-1 font-semibold transition-colors duration-200 ease-in-out text-lg/normal text-secondary-inverse hover:text-accent"><i class="fa-regular fa-eye"></i></a>
                        </td>
                      </tr>';
                }
                else if (isset($_SESSION['log_user_type']) and $_SESSION['log_user_type'] == 'adviser' and isset($_GET['dashboardTable']) && $_GET['dashboardTable'] == 'request') {
                    echo '<tr class="border-b border-dashed last:border-b-0 p-3">
                        <td class="p-3 text-start">
                            <span class="font-semibold text-light-inverse text-sm">' . $row['first_name'] . ' ' . $row['last_name'] . '</span>
                        </td>
                        <td class="p-3 text-start">
                            <span class="font-semibold text-light-inverse text-sm">' . $row['OJT_adviser'] . '</span>
                        </td>
                        <td class="p-3 text-end">
                            <span class="font-semibold text-light-inverse text-sm">' . $row["program"] . '</span>
                        </td>
                        <td class="p-3 text-end">
                            <span class="font-semibold text-light-inverse text-sm">' . $row["section"] . '</span>
                        </td>
                        <td class="p-3 text-end ">
                            <a href="flipbook.php?view=' . urlencode(encrypt_data($row['narrative_id'], $secret_key)) .'" target="_blank" class="hover:cursor-pointer mb-1 font-semibold transition-colors duration-200 ease-in-out text-lg/normal text-secondary-inverse hover:text-accent mr-2"><i class="fa-regular fa-eye"></i></a>
                        </td>
                      </tr>';
                }
                else if (isset($_SESSION['log_user_type']) and $_SESSION['log_user_type'] == 'admin' and isset($_GET['dashboardTable']) && $_GET['dashboardTable'] == 'request') {
                    echo '<tr class="border-b border-dashed last:border-b-0 p-3">
                        <td class="p-3 text-start">
                            <span class="font-semibold text-light-inverse text-md/normal">' . $row['first_name'] . ' ' . $row['last_name'] . '</span>
                        </td>
                        <td class="p-3 text-start">
                            <span class="font-semibold text-light-inverse text-md/normal">' . $row['OJT_adviser'] . '</span>
                        </td>
                        <td class="p-3 text-end">
                            <span class="font-semibold text-light-inverse text-md/normal">' . $row["program"] . '</span>
                        </td>
                        <td class="p-3 text-end">
                            <span class="font-semibold text-light-inverse text-md/normal">' . $row["section"] . '</span>
                        </td>
                        <td class="p-3 text-end ">
                            <a href="flipbook.php?view=' . urlencode(encrypt_data($row['narrative_id'], $secret_key)) .'" target="_blank" class="hover:cursor-pointer mb-1 font-semibold transition-colors duration-200 ease-in-out text-lg/normal text-secondary-inverse hover:text-accent mr-2"><i class="fa-regular fa-eye"></i></a>
                            <a onclick="openModalForm(\'EditNarrative\');editNarrative(this.getAttribute(\'data-narrative\'))" id="archive_narrative" data-narrative="' . urlencode(encrypt_data($row['narrative_id'], $secret_key)) .'" class="hover:cursor-pointer mb-1 font-semibold transition-colors duration-200 ease-in-out text-lg/normal text-secondary-inverse hover:text-info"><i class="fa-solid fa-pen-to-square"></i></a>
                        </td>
                      </tr>';
                }else{

                    exit();
                }
            }
        }
    }
    $conn->close();
}
if ($action == 'narrativeReportsJson'){

    $narrative_id = decrypt_data($_GET['narrative_id'], $secret_key);

    $sql = "SELECT * FROM narrativereports WHERE narrative_id = ? ORDER BY upload_date DESC LIMIT 1";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $narrative_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $row['narrative_id'] = encrypt_data($row['narrative_id'], $secret_key);
            echo json_encode($row);
        } else {
            echo "Error: No data found for the given narrative ID.";
        }
        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }
}

if ($action === 'UpdateNarrativeReport'){

    $first_name = isset($_POST['first_name']) ? sanitizeInput($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? sanitizeInput($_POST['last_name']) : '';
    $program = isset($_POST['program']) ? sanitizeInput($_POST['program']) : '';
    $section = isset($_POST['section']) ? sanitizeInput($_POST['section']) : '';
    $ojt_adviser = isset($_POST['ojt_adviser']) ? sanitizeInput($_POST['ojt_adviser']) : '';
    $stud_sex = isset($_POST['stud_Sex']) ? sanitizeInput($_POST['stud_Sex']) : '';
    $school_id = isset($_POST['school_id']) && is_numeric($_POST['school_id']) ? sanitizeInput($_POST['school_id']) : '';
    $narrative_id = isset($_POST['narrative_id']) ? sanitizeInput($_POST['narrative_id']) : '';
    if ($first_name !== '' && $last_name !== '' && $stud_sex !== '' && $program !== '' && $section !== '' && $ojt_adviser !== '' && $school_id !== ''  && $narrative_id !== '') {
        $file_first_name = str_replace(' ', '', $first_name);
        $file_last_name = str_replace(' ', '', $last_name);
        $new_file_name = $file_first_name."_".$file_last_name."_".$program."_".$section."_".$school_id.".pdf";
        $current_date_time = date('Y-m-d H:i:s');
        $narrative_status = 'OK';
        $narrative_id = decrypt_data($narrative_id,$secret_key);
        $old_filename = '';
        $sql = "SELECT * FROM narrativereports WHERE narrative_id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $narrative_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $old_filename = $row['narrative_file_name'];
            }
        }
        $update_final_report = $conn->prepare("UPDATE narrativereports
                                      SET stud_school_id = ?,
                                          sex = ?,
                                          first_name = ?,
                                          last_name = ?,
                                          program = ?,
                                          section = ?,
                                          OJT_adviser = ?,
                                          narrative_file_name = ?,
                                          upload_date = ?,
                                          file_status = ?
                                      WHERE narrative_id = ?");
        $update_final_report->bind_param("ssssssssssi",
            $school_id, $stud_sex,$first_name, $last_name,
            $program, $section, $ojt_adviser, $new_file_name,
            $current_date_time, $narrative_status,$narrative_id);


        if (!$update_final_report->execute()){
            echo 'query error';
            exit();
        }else{
            if (isset($_FILES['final_report_file']) && $_FILES['final_report_file']['error'] === UPLOAD_ERR_OK){
                //replace existing by deleting and converting new

                $file_name = $_FILES['final_report_file']['name'];
                $file_temp = $_FILES['final_report_file']['tmp_name'];
                $file_type = $_FILES['final_report_file']['type'];
                $file_error = $_FILES['final_report_file']['error'];
                $file_size = $_FILES['final_report_file']['size'];

                if (isPDF($file_name)){
                    $pdf = 'src/NarrativeReportsPDF/'.$old_filename;
                    $flipbook_page_dir = 'src/NarrativeReports_Images/'. str_replace('.pdf','',$old_filename);
                    if (!delete_pdf($pdf) or !deleteDirectory($flipbook_page_dir)){
                        echo 'dir not deleted';
                        exit();
                    }

                    $file_first_name = str_replace(' ', '', $first_name);
                    $file_last_name = str_replace(' ', '', $last_name);
                    $new_file_name = $file_first_name."_".$file_last_name."_".$program."_".$section."_".$school_id.".pdf";
                    $pdf_file_path = "src/NarrativeReportsPDF/" . $new_file_name;
                    move_uploaded_file($file_temp, $pdf_file_path);
                    $report_pdf_file_name = $file_first_name."_".$file_last_name."_".$program."_".$section."_".$school_id;
                    if (convert_pdf_to_image($report_pdf_file_name)){
                        echo 1;
                        exit();
                    }
                    else{
                        echo 'error Conversion';
                    }
                }

            }
            else {
                //Nirerename lang ung pdf sa src/NarrativeReportsPDF/  tapos directory
                // at ung lamang ng directory sa src/NarrativeReports_Images/
                // ung rename nito naka base lang sa laman ng database
                // ang purpose para ma reuse ang existing files

                $narrative_reportPDF_path = 'src/NarrativeReportsPDF/';
                $narrative_reportIMG_path = 'src/NarrativeReports_Images/';
                if (is_dir($narrative_reportPDF_path)) {
                    if ($handle = opendir($narrative_reportPDF_path)) {
                        // Rename the PDF file
                        while (false !== ($file = readdir($handle))) {
                            if (pathinfo($file, PATHINFO_EXTENSION) == 'pdf' && $file == $old_filename) {
                                // Rename the pdf file
                                $oldFilePath = $narrative_reportPDF_path . $old_filename;
                                $newFilePath = $narrative_reportPDF_path . $new_file_name;
                                if (rename($oldFilePath, $newFilePath)) {
                                    // Rename the flip book image directory
                                    $old_flipbook_page_directory = str_replace('.pdf', '', $old_filename);
                                    $new_flipbook_page_directory = str_replace('.pdf', '', $new_file_name);
                                    if (is_dir($narrative_reportIMG_path . $old_flipbook_page_directory)) {
                                        if (rename($narrative_reportIMG_path . $old_flipbook_page_directory, $narrative_reportIMG_path . $new_flipbook_page_directory)) {
                                            // Rename image files inside the flip book directory
                                            if (is_dir($narrative_reportIMG_path . $new_flipbook_page_directory)) {
                                                if ($handle_img = opendir($narrative_reportIMG_path . $new_flipbook_page_directory)) {
                                                    while (false !== ($file_img = readdir($handle_img))) {
                                                        if ($file_img != "." && $file_img != "..") {
                                                            // Construct the new filename based on the new directory name pattern
                                                            $oldImagePath = $narrative_reportIMG_path . $new_flipbook_page_directory . "/" . $file_img;
                                                            $newImageName = str_replace($old_flipbook_page_directory, $new_flipbook_page_directory, $file_img);
                                                            $newImagePath = $narrative_reportIMG_path . $new_flipbook_page_directory . "/" . $newImageName;

                                                            // Rename the image file
                                                            if (!rename($oldImagePath, $newImagePath)) {
                                                                echo "* Error renaming image file.";
                                                                echo 0;
                                                                exit();
                                                            }
                                                        }
                                                    }
                                                    closedir($handle_img);
                                                } else {
                                                    echo "Error opening image directory.";
                                                    echo 0;
                                                    exit();
                                                }
                                            } else {
                                                echo "New directory does not exist.";
                                                echo 0;
                                                exit();
                                            }
                                        } else {
                                            echo "Error renaming directory.";
                                            echo 0;
                                            exit();
                                        }
                                    } else {
                                        echo "Directory does not exist.";
                                        echo 0;
                                        exit();
                                    }
                                } else {
                                    echo "Error renaming PDF file.";
                                    echo 0;
                                    exit();
                                }
                            }
                        }
                        closedir($handle);
                    } else {
                        echo "Error opening PDF directory.";
                        echo 0;
                        exit();
                    }
                } else {
                    echo "PDF directory does not exist.";
                    echo 0;
                    exit();
                }
                echo 1;
                exit();
            }
        }
    }
}

if ($action == 'ArchiveNarrativeReport'){

    $narrative_id = isset($_POST['narrative_id']) ? sanitizeInput($_POST['narrative_id']) : '';
    if ($narrative_id !== ''){
        $narrative_id = decrypt_data($narrative_id, $secret_key);
        $file_status = 'Archived';
        $archive_final_report = $conn->prepare("UPDATE narrativereports
                                      SET 
                                          file_status = ?
                                      WHERE narrative_id = ?");
        $archive_final_report->bind_param('si',$file_status, $narrative_id);
        if (!$archive_final_report->execute()){
            echo 'Query Error';
            exit();
        }
        echo 1;
        exit();
    }else{
        echo 2;// empty id
        exit();
    }
}

if ($action == 'newUser') {


    $user_first_name = isset($_POST['user_Fname']) ? sanitizeInput($_POST['user_Fname']) : '';
    $user_last_name = isset($_POST['user_Lname']) ? sanitizeInput($_POST['user_Lname']) : '';
    $user_shc_id = isset($_POST['school_id']) ? sanitizeInput($_POST['school_id']) : '';
    $user_sex = isset($_POST['user_Sex']) ? sanitizeInput($_POST['user_Sex']) : '';
    $user_contact_number = isset($_POST['contactNumber']) ? sanitizeInput($_POST['contactNumber']) : '';
    $user_address = isset($_POST['user_address']) ? sanitizeInput($_POST['user_address']) : '';
    $user_program = isset($_POST['stud_Program']) ? sanitizeInput($_POST['stud_Program']) : '';
    $user_section = isset($_POST['stud_Section']) ? sanitizeInput($_POST['stud_Section']) : '';
    $user_email = isset($_POST['user_Email']) ? sanitizeInput($_POST['user_Email']) : '';
    $user_type = isset($_POST['user_type']) ?sanitizeInput($_POST['user_type']) : '';
    $user_password = isset($_POST['user_password']) && sanitizeInput($_POST['user_password']) ? $_POST['user_password'] :'';

    if ($user_first_name !== '' &&
        $user_last_name !== '' &&
        $user_shc_id !== '' &&
        $user_sex !== '' &&
        $user_contact_number !== '' &&
        $user_address !== '' &&
        $user_type !== '' &&
        $user_email !== '') {
        $check_sql = "SELECT user_id FROM tbl_user_info WHERE school_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $newStud_shc_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            // Student ID already exists, echo "2" and exit
            echo 2;
            exit();
        }

        if ( $user_program !== '' && $user_section !== '' && $user_password == '') {
            $user_password = generatePassword($user_shc_id);
        }

        $hashed_password = password_hash($user_password, PASSWORD_DEFAULT);

        // Insert the new student user into the database
        $insert_sql = "INSERT INTO tbl_user_info (first_name, last_name, address, contact_number, school_id, sex, user_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sssssss", $user_first_name, $user_last_name, $user_address, $user_contact_number,
            $user_shc_id, $user_sex,$user_type);
        $insert_stmt->execute();

        // Get the user_id of the newly inserted student user
        $user_id = $insert_stmt->insert_id;

        // Insert the student's account details into tbl_accounts
        $account_sql = "INSERT INTO tbl_accounts (user_id, email, password, status) 
                VALUES (?, ?, ?, 'active')";
        $account_stmt = $conn->prepare($account_sql);
        $account_stmt->bind_param("iss", $user_id, $user_email, $hashed_password);
        $account_stmt->execute();

        if ( $user_program !== '' && $user_section !== '' && $user_type == 'student')
        {
            $student_sql = "INSERT INTO tbl_students (user_id, program_id, section_id) 
                VALUES (?, ?, ?)";
            $student_stmt = $conn->prepare($student_sql);
            $student_stmt->bind_param("iii", $user_id, $user_program, $user_section);
            $student_stmt->execute();
        }
        echo 1;

    } else {
        // Output error message if any required field is empty
        echo 'Some required fields are empty.';
    }
}
if ($action == 'getStudentsList'){
    $fetch_enrolled_stud =  "SELECT 
                        u.user_id,
                        u.first_name,
                        u.last_name,
                        u.address,
                        u.contact_number,
                        u.sex,
                        u.school_id,
                        u.user_type,
                        s.program_id,
                        p.program_code,
                        p.program_name,
                        a.acc_id,
                        a.email,
                        a.password,
                        a.status,
                        a.date_created,
                        se.section_id,
                        se.section
                    FROM 
                        tbl_students s
                    JOIN 
                        tbl_user_info u ON s.user_id = u.user_id
                    JOIN 
                        program p ON s.program_id = p.program_id
                    JOIN 
                        tbl_accounts a ON s.user_id = a.user_id
                    JOIN 
                        section se ON s.section_id = se.section_id
                    where a.status = 'active' and u.user_type = 'student'
                    ORDER BY 
                        a.date_created ASC";
    $result = $conn->query($fetch_enrolled_stud);
    if ($result === false){
        echo "Error: " . $conn->error;
    }
    if ($result->num_rows > 0){
        while ($row = $result->fetch_assoc()){
            echo '<tr class="border-b border-dashed last:border-b-0 p-3">
                        <td class="p-3 text-start">
                            <span class="font-semibold text-light-inverse text-md/normal">'.$row['school_id'].'</span>
                        </td>
                        <td class="p-3 text-start">
                            <span class="font-semibold text-light-inverse text-md/normal">'.$row['first_name'].' '.$row['last_name'].'</span>
                        </td>

                
                        <td class="p-3 text-end">
                            <span class="font-semibold text-light-inverse text-md/normal">'.$row['section'].'</span>
                        </td>
                        <td class="p-3 text-end">
                            <span class="font-semibold text-light-inverse text-md/normal">'.$row['program_code'].'</span>
                        </td>
                        <td class="p-3 text-end">
                            <a href="#" onclick="openModalForm(\'editStuInfo\');editUserStud_Info(this.getAttribute(\'data-id\'))" data-id="' . urlencode(encrypt_data($row['user_id'], $secret_key)) .'" class="hover:cursor-pointer mb-1 font-semibold transition-colors duration-200 ease-in-out text-lg/normal text-secondary-inverse hover:text-accent"><i class="fa-solid fa-circle-info"></i></a>
                        </td>
                    </tr>';
            /*
            echo "User ID: " . $row['user_id'] . "<br>";
            echo "First Name: " . $row['first_name'] . "<br>";
            echo "Last Name: " . $row['last_name'] . "<br>";
            echo "Address: " . $row['address'] . "<br>";
            echo "Contact Number: " . $row['contact_number'] . "<br>";
            echo "Sex: " . $row['sex'] . "<br>";
            echo "School ID: " . $row['school_id'] . "<br>";
            echo "Program ID: " . $row['program_id'] . "<br>";
            echo "Program Code: " . $row['program_code'] . "<br>";
            echo "Program Name: " . $row['program_name'] . "<br>";
            echo "Account ID: " . $row['acc_id'] . "<br>";
            echo "Email: " . $row['email'] . "<br>";
            echo "Date Created: " . $row['date_created'] . "<br>";
            echo "Section ID: " . $row['section_id'] . "<br>";
            echo "Section: " . $row['section'] . "<br>";
            echo "<br>"; // Add a line break between each student's information
            */
        }
    }
}

if ($action == 'getStudInfoJson') {
    $user_id = decrypt_data($_GET['data_id'], $secret_key);

    $fetch_enrolled_stud = "SELECT 
                                u.user_id,
                                u.first_name,
                                u.last_name,
                                u.address,
                                u.contact_number,
                                u.sex,
                                u.school_id,
                                s.program_id,
                                p.program_code,
                                p.program_name,
                                a.acc_id,
                                a.email,
                                a.password,
                                a.date_created,
                                a.status,
                                se.section_id,
                                se.section
                            FROM 
                                tbl_students s
                            JOIN 
                                tbl_user_info u ON s.user_id = u.user_id
                            JOIN 
                                program p ON s.program_id = p.program_id
                            JOIN 
                                tbl_accounts a ON s.user_id = a.user_id
                            JOIN 
                                section se ON s.section_id = se.section_id
                            WHERE u.user_id = ?
                            ORDER BY 
                                a.date_created ASC
                            LIMIT 1";

    $stmt = $conn->prepare($fetch_enrolled_stud);
    $stmt->bind_param("i", $user_id);

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        $error = "Error: " . $stmt->error;
        header('Content-Type: application/json'); // Add this line
        echo json_encode(array("error" => $error));
    } else {
        $student = $result->fetch_assoc();
        header('Content-Type: application/json'); // Add this line
        echo json_encode($student);
    }
    $stmt->close();
}




if ($action == 'updateUserInfo'){
    $editUser_first_name = isset($_POST['user_Fname']) ? sanitizeInput($_POST['user_Fname']) : '';
    $editUser_last_name = isset($_POST['user_Lname']) ? sanitizeInput($_POST['user_Lname']) : '';
    $editUser_shc_id = isset($_POST['school_id']) ? sanitizeInput($_POST['school_id']) : '';
    $editUser_sex = isset($_POST['user_Sex']) ? sanitizeInput($_POST['user_Sex']) : '';
    $editUser_contact_number = isset($_POST['contactNumber']) ? sanitizeInput($_POST['contactNumber']) : '';
    $editUser_address = isset($_POST['user_address']) ? sanitizeInput($_POST['user_address']) : '';
    $editStud_program = isset($_POST['stud_Program']) ? sanitizeInput($_POST['stud_Program']) : '';
    $editStud_section = isset($_POST['stud_Section']) ? sanitizeInput($_POST['stud_Section']) : '';
    $editUser_email = isset($_POST['user_Email']) ? sanitizeInput($_POST['user_Email']) : '';
    $editUser_user_id = isset($_POST['user_id']) ? sanitizeInput($_POST['user_id']) : '';
    $edituser_type = isset($_POST['user_type']) && sanitizeInput($_POST['user_type']) ? $_POST['user_type']: '';


    if ($editUser_first_name !== '' &&
        $editUser_last_name !== '' &&
        $editUser_shc_id !== '' &&
        $editUser_sex !== '' &&
        $editUser_contact_number !== '' &&
        $editUser_address !== '' &&
        $editUser_user_id !== '' &&
        $editUser_email !== ''&&
        $edituser_type !== '') {


        // Proceed with updating student information
        $sql = "UPDATE tbl_user_info 
                SET first_name = ?, 
                    last_name = ?, 
                    address = ?, 
                    contact_number = ?, 
                    sex = ?, 
                    school_id = ?,
                    user_type = ?
                WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $editUser_first_name, $editUser_last_name, $editUser_address, $editUser_contact_number, $editUser_sex, $editUser_shc_id,$edituser_type, $editUser_user_id);
        $stmt->execute();


        if ($stmt->errno == 1062) {
            // Error code 1062  duplicate entry error
            echo 2;// duplicate stud id
            exit; // Stop execution
        } else if ($stmt->errno) {
            // Handle other MySQL errors
            echo 'Error: ' . $stmt->error;
            exit; // Stop execution
        }
        if ($editStud_program !== '' && //execute only if the admin editing student type user
            $editStud_section !== '' && $edituser_type == 'student'){
            $update_stud_info = "UPDATE tbl_students 
                            SET program_id = ?, 
                                section_id = ? 
                            WHERE user_id = ?";
            $stmt_update_info = $conn->prepare($update_stud_info);
            $stmt_update_info->bind_param("iii", $editStud_program, $editStud_section, $editUser_user_id);
            $stmt_update_info->execute();
        }

        if (isset($_POST['user_Pass']) and sanitizeInput($_POST['user_Pass'])){
            $hashed_password = password_hash($_POST['user_Pass'], PASSWORD_DEFAULT);
            $update_account = "UPDATE tbl_accounts 
                           SET email = ?, 
                               password = ? 
                           WHERE user_id = ?";
            $stmt_update_account = $conn->prepare($update_account);
            $stmt_update_account->bind_param("ssi", $editUser_email, $hashed_password, $editUser_user_id);
            $stmt_update_account->execute();

            //add emailing
        }
        echo 1;

    } else {
        echo 'Error: Some required fields are empty.';
    }
}

if ($action == 'deactivate_account'){
    $user_id = isset($_GET['data_id']) && sanitizeInput($_GET['data_id']) ? $_GET['data_id'] : '';
    if (isset($user_id)){
        $sql = "UPDATE tbl_accounts SET status = 'inactive'  where user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i',$user_id);
        if ($stmt->execute()){
            echo 1;
        }
    }
}

if ($action == 'getAdvisers') {
    // Prepare the SQL query to fetch advisers and admins
    $sql = "SELECT ui.*, acc.*
        FROM tbl_user_info ui
        INNER JOIN tbl_accounts acc ON ui.user_id = acc.user_id
        WHERE ui.user_type IN ('adviser', 'admin')";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {

        $advisers = array();

        // Fetch data row by row
        while ($row = $result->fetch_assoc()) {
            echo '<tr class="border-b border-dashed last:border-b-0 p-3">
                        <td class="p-3 text-start">
                            <span class="font-semibold text-light-inverse text-md/normal">'.$row['school_id'].'</span>
                        </td>
                        <td class="p-3 text-start">
                            <span class="font-semibold text-light-inverse text-md/normal">'.$row['first_name'].' '.$row['last_name'].'</span>
                        </td>
                        <td class="p-3 text-end">
                            <span class="font-semibold text-light-inverse text-md/normal">'.getTotalAdvList($row['user_id']).'</span>
                        </td>
                        <td class="p-3 text-end">
                            <a onclick="openModalForm(\'editAdv_admin\');editAdvInfo(this.getAttribute(\'data-id\'))" data-id="'.$row['user_id'].'" href="#" class="hover:cursor-pointer mb-1 font-semibold transition-colors duration-200 ease-in-out text-lg/normal text-secondary-inverse hover:text-accent"><i class="fa-solid fa-circle-info"></i></a>
                        </td>
                    </tr>';
        }
    }
    exit();
}
if ($action == 'getAdvInfoJson') {
    $user_id = $_GET['data_id']; // Assuming you receive the user_id from the client-side

    $sql = "SELECT ui.*, acc.*
            FROM tbl_user_info ui
            INNER JOIN tbl_accounts acc ON ui.user_id = acc.user_id
            WHERE ui.user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Fetch the result
    $result = $stmt->get_result();
    if ($result === false) {
        $error = "Error: " . $stmt->error;
        header('Content-Type: application/json'); // Add this line
        echo json_encode(array("error" => $error));
    }else {
        $advisers = $result->fetch_assoc();
        header('Content-Type: application/json'); // Add this line
        echo json_encode($advisers);
    }
    $stmt->close();
}




