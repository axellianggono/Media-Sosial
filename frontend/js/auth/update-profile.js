const profilePhoto = document.getElementById("profile-photo");
const profilePhotoInput = document.getElementById("profile_photo");
const username = document.getElementById("username");
const email = document.getElementById("email");
const updateButton = document.getElementById("update-button");
const deleteButton = document.getElementById("delete-button");

async function fetchProfile() {
  const authToken = localStorage.getItem("auth_token");
  const response = await fetch("../../api/auth/get.php", {
    method: "GET",
    headers: {
      Authorization: "Bearer " + authToken,
    },
  });

  const result = await response.json();
  if (response.ok) {
    username.value = result.data.username;
    email.value = result.data.email;
    profilePhoto.src = result.data.profile_photo;
  } else {
    alert("Failed to fetch profile: " + result.message);
    if (response.status === 401) {
      window.location.href = "login.html";
    }
  }
}

profilePhotoInput.addEventListener("change", async (e) => {
  e.preventDefault();
  const file = profilePhotoInput.files[0];

  if (file) {
    const reader = new FileReader();

    reader.onload = async function (event) {
      profilePhoto.src = event.target.result;
      preview.style.display = "block";
    };

    reader.readAsDataURL(file);
  } else {
    preview.style.display = "none";
  }
});

async function updateProfile() {
  const authToken = localStorage.getItem("auth_token");
  const formData = new FormData();
  formData.append("_method", "PUT");
  formData.append("username", username.value);

  // Append new password if provided
  const oldPassword = document.getElementById("old-password").value;
  const newPassword = document.getElementById("new-password").value;

  if (oldPassword) {
    formData.append("old_password", oldPassword);
  }

  if (newPassword) {
    formData.append("new_password", newPassword);
  }

  if (profilePhotoInput.files[0]) {
    formData.append("profile_photo", profilePhotoInput.files[0]);
  }
  const response = await fetch("../../api/auth/update.php", {
    method: "POST",
    headers: {
      Authorization: "Bearer " + authToken,
    },
    body: formData,
  });
  const result = await response.json();

  if (response.ok) {
    alert(result.message);
    await fetchProfile();
  } else {
    alert("Update failed: " + result.message);
  }
}

async function deleteAccount() {
  const authToken = localStorage.getItem("auth_token");
  const response = await fetch("../../api/auth/delete.php", {
    method: "DELETE",
    headers: {
      Authorization: "Bearer " + authToken,
    },
  });
  const result = await response.json();
  if (response.ok) {
    alert(result.message);
    localStorage.removeItem("auth_token");
    window.location.href = "register.html";
  } else {
    alert("Account deletion failed: " + result.message);
  }
}

window.addEventListener("load", async () => {
  await fetchProfile();
});

updateButton.addEventListener("click", async (e) => {
  e.preventDefault();
  await updateProfile();
});

deleteButton.addEventListener("click", async (e) => {
  e.preventDefault();
  if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
    await deleteAccount();
  }
});