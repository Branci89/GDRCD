$(function(){

    Form('#online_time_form','pages/online_status/online_status_ajax.php',statusSuccess);

    function statusSuccess(data){

        if(data != ''){

            let datas = JSON.parse(data);

            if(datas.response){
                $('.floating_box_status').remove();
            }

            alert(datas.mex);
        }

    }

    $('.floating_box_status .change-dimension').on('click',function(){
        $(this).closest('.floating_box_status').toggleClass('minimized');
    })

});