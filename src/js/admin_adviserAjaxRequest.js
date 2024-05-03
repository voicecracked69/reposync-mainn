
document.addEventListener('submit', function(e) {
    e.preventDefault();
    let modal,formData,endpoint,loader_id,btn

     if (e.target.id === 'narrativeReportsForm'){
        endpoint = 'newFinalReport'
        modal =  'newNarrative';
        loader_id = 'loader_narrative';
        btn = 'newNarrativeSubmitbtn'
    }if (e.target.id === 'EditNarrativeReportsForm'){
         if (e.submitter.id === 'update_btn'){
             endpoint = 'UpdateNarrativeReport';
         }
         else if (e.submitter.id === 'archive_btn'){
             endpoint = 'ArchiveNarrativeReport'
         }

         loader_id = 'loader_narrative_update';
         btn = 'editNarrativeBtn';
         modal = 'EditNarrative';
    }if (e.target.id === 'studentForm'){
         if (e.submitter.id === 'stud_Submit') {
             endpoint = "newUser";
         }
        modal = 'newStudentdialog';
        btn = 'newStudBtn';
        loader_id = 'newStudentLoader';
    }if (e.target.id === 'EditStudentForm'){
         if (e.submitter.id === 'update_stud_btn'){
             endpoint = 'updateUserInfo';
         }
        modal = 'editStuInfo';
         btn = 'editStudBtn';
         loader_id = 'editStudentLoader'
    }
     if (e.target.id === 'admin_adv_Form'){
         endpoint = 'newUser';
         let password = e.target.querySelector('input[name="user_password"]').value;
         let confirmPassword = e.target.querySelector('input[name="user_confPass"]').value;
         if (password !== confirmPassword) {
             alert("Passwords do not match. Please try again.");
             return false;
         }
         loader_id ='new_adv_adminLoader'
         btn = 'new_adv_adminBtn';
         modal = 'newAdvierDialog';
     }
     if (e.target.id === 'EditAdviserForm'){
         endpoint = 'updateUserInfo';
         loader_id = 'editAdVLoader';
         btn = 'editStudBtn'
         modal = 'editAdv_admin';
     }


    formData = new FormData(e.target);
    add_loader(loader_id);
    disable_button(btn)

    $.ajax({
        url: '../ajax.php?action='+ endpoint,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (parseInt(response) === 1) {
                enable_button(btn)
                remove_loader(loader_id);
                closeModalForm(modal);
                dashboard_student_NarrativeReports();
                get_studenUsertList();
                get_AdvUsertList();
            } else if (parseInt(response) === 2){
               alert("Student id already exist");// para sa create new student user hindi pa final
               remove_loader(loader_id);
               enable_button(btn);
            }else {
                console.log(response);
            }
            e.target.reset();
        },
    });

});

function isEmpty(variable) {
    return variable === null ||
        variable === undefined ||
        variable === '' ||
        (Array.isArray(variable) && variable.length === 0);
}
