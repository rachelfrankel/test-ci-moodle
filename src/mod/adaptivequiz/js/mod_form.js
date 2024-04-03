document.addEventListener("DOMContentLoaded", function() {
    var browsertimer = document.querySelector("[name=\'browsertimer\']");
    var timeinput = document.querySelector("[name=\'timeinput\']");
    var submitBtn2 = document.querySelector("[name=\'submitbutton2\']");
    var submitBtn = document.querySelector("[name=\'submitbutton\']");
    
    function updateButtonState() {
        var addtimer = browsertimer.value;
        var maxtime = timeinput.value;
        if(addtimer == "1" && maxtime == '') {
            submitBtn.disabled = true;
            submitBtn2.disabled = true;
        } else {
            submitBtn.disabled = false;
            submitBtn2.disabled = false;
        }
    }

    browsertimer.addEventListener("change", updateButtonState);
    timeinput.addEventListener("input", updateButtonState);
});
