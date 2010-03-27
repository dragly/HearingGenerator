jQuery(document).ready(function($) {
    $(".useargument").click(function() {
        if($(this).attr("checked") == true) {
            $("#argument" + $(this).attr("num")).show()
        } else {
            $("#argument" + $(this).attr("num")).hide()
        }
    });
    $(".useargument").attr("checked", true);
    $(".useargument").click();
    $(".useargument").attr("checked", true);
});
