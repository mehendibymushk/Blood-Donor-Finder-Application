function validatePhone() {

    let phone = document.getElementById("phone");

    if (phone && phone.value.length < 10) {

        alert("Please enter a valid phone number.");

        return false;
    }

    return true;
}