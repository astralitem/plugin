document.addEventListener("DOMContentLoaded", function () {

    /* ================= ACCORDION ================= */
    const head = document.querySelector(".scx-dota-head");
    const body = document.querySelector(".scx-dota-content");
    const toggle = document.querySelector(".scx-dota-toggle");

    if (head && body && toggle) {
        head.addEventListener("click", function () {
            const open = body.style.display === "block";
            body.style.display = open ? "none" : "block";
            toggle.textContent = open ? "+" : "–";
        });
    }

    /* ================= CUSTOM SELECT (ALL TYPES) ================= */
    document.querySelectorAll(".scx-select, .scx-form-select").forEach(function (select) {
        const trigger = select.querySelector(".scx-select-trigger");
        const options = select.querySelector(".scx-select-options");
        const hidden = select.querySelector("input[type=hidden]");

        if (!trigger || !options || !hidden) return;

        trigger.addEventListener("click", function (e) {
            e.stopPropagation();

            // Close other open selects
            document.querySelectorAll(".scx-select-options").forEach(function (o) {
                if (o !== options) o.style.display = "none";
            });

            options.style.display = (options.style.display === "block") ? "none" : "block";
        });

        options.querySelectorAll(".scx-option").forEach(function (opt) {
            opt.addEventListener("click", function (e) {
                e.stopPropagation();
                trigger.textContent = opt.textContent;
                hidden.value = opt.dataset.value;
                options.style.display = "none";
            });
        });
    });

    document.addEventListener("click", function () {
        document.querySelectorAll(".scx-select-options").forEach(function (o) {
            o.style.display = "none";
        });
    });

    /* ================= STARS ================= */
    document.querySelectorAll(".scx-stars").forEach(function (box) {
        const stars = box.querySelectorAll("span");
        const input = box.nextElementSibling;

        if (!input) return;

        stars.forEach(function (star) {
            star.addEventListener("click", function () {
                const value = parseInt(star.dataset.star, 10);
                input.value = value;

                stars.forEach(function (s) {
                    s.classList.toggle("active", parseInt(s.dataset.star, 10) <= value);
                });
            });
        });
    });

    /* ================= MEDALS ================= */
    document.querySelectorAll('.scx-medal-card').forEach(function (card) {
        const medals = card.querySelectorAll('.scx-medal');
        const input = card.querySelector('input[name="dota_medal"]');

        medals.forEach(function (medal) {
            medal.addEventListener('click', function () {
                medals.forEach(function (m) { m.classList.remove('active'); });
                medal.classList.add('active');
                if (input) input.value = medal.dataset.value;
            });
        });
    });

    /* ================= ACCOUNT FORM ================= */

    /* MULTI IMAGE UPLOAD (MAX 10) */
    const uploadBtn = document.querySelector(".scx-upload-btn");
    const uploadInput = document.getElementById("scx-upload-input");
    const previews = document.querySelector(".scx-upload-previews");

    if (uploadBtn && uploadInput && previews) {

        uploadBtn.addEventListener("click", function () {
            uploadInput.click();
        });

        uploadInput.addEventListener("change", function () {
            const files = Array.from(uploadInput.files).slice(0, 10);
            previews.innerHTML = "";

            files.forEach(function (file) {
                if (!file.type.startsWith("image/")) return;

                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.createElement("img");
                    img.src = e.target.result;
                    previews.appendChild(img);
                };
                reader.readAsDataURL(file);
            });

            if (uploadInput.files.length > 10) {
                alert("حداکثر 10 تصویر مجاز است.");
            }
        });
    }

});





/* ================= ACCOUNT FORM ================= */

const submitBtn = document.querySelector(".scx-submit-btn");

if (submitBtn) {
    submitBtn.addEventListener("click", function () {

        const formData = new FormData();

        // Basic form fields
        document.querySelectorAll(".scx-account-form input, .scx-account-form textarea").forEach(function (field) {
            if (field.name) {
                formData.append(field.name, field.value);
            }
        });

        // Steam profile data (from rendered UI)
        formData.append("steam_avatar", document.querySelector(".scx-avatar-wrap img")?.src || "");
        formData.append("steam_name", document.querySelector(".scx-header-info h2")?.innerText || "");
        formData.append("steam_level", document.querySelector(".scx-badge.level")?.innerText.replace("Lv", "").trim() || "");
        formData.append("steam_profile_url", document.querySelector(".scx-profile-link")?.href || "");

        // Ban data
        formData.append("ban_community", document.querySelector(".scx-ban-item:nth-child(1)")?.classList.contains("bad") ? "1" : "0");
        formData.append("ban_trade", document.querySelector(".scx-ban-item:nth-child(2)")?.classList.contains("bad") ? "1" : "0");
        formData.append("ban_game", document.querySelector(".scx-ban-item:nth-child(3)")?.classList.contains("bad") ? "1" : "0");
        formData.append("ban_vac", document.querySelector(".scx-ban-item:nth-child(4)")?.classList.contains("bad") ? "1" : "0");

        // Stats
        document.querySelectorAll(".scx-stat").forEach(function (stat) {
            const label = stat.querySelector("span")?.innerText;
            const value = stat.querySelector("strong")?.innerText;
            if (label && value) {
                formData.append(label.toLowerCase().replace(/\s+/g, "_"), value);
            }
        });

        // Game library
        const games = [];
        document.querySelectorAll(".scx-game-card").forEach(function (card) {
            games.push({
                name: card.querySelector(".scx-game-title")?.innerText || "",
                hours: card.querySelector(".scx-game-hours")?.innerText || "",
                price: card.querySelector(".scx-game-price, .scx-game-free")?.innerText || "",
                image: card.querySelector("img")?.src || ""
            });
        });
        formData.append("games_library", JSON.stringify(games));

        // Dota fields
        document.querySelectorAll(".scx-dota-content input").forEach(function (field) {
            if (field.name) {
                formData.append(field.name, field.value);
            }
        });

        // Uploaded images
        const uploadInput = document.getElementById("scx-upload-input");
        if (uploadInput && uploadInput.files.length) {
            Array.from(uploadInput.files).forEach(function (file) {
                formData.append("account_images[]", file);
            });
        }

        // AJAX meta
        formData.append("action", "scx_submit_account");
        formData.append("nonce", scx_ajax.nonce);

        fetch(scx_ajax.ajax_url, {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("آکانت با موفقیت ثبت شد!");
                window.location.reload();
            } else {
                alert("خطا: " + data.data);
            }
        })
        .catch(() => alert("خطا در ارسال فرم"));
    });
}






/* برای اسلاید عکس ها در صفحه محصول تکی  */
jQuery(function($){

let slides = $('.scx-pro-slide');
let thumbs = $('.scx-thumb');
let index = 0;

/* SHOW */
function showSlide(i){
    slides.removeClass('active').eq(i).addClass('active');
    thumbs.removeClass('active').eq(i).addClass('active');
    index = i;
}

/* ARROWS */
$('.scx-pro-next').click(function(){
    let i = index+1;
    if(i>=slides.length) i=0;
    showSlide(i);
});

$('.scx-pro-prev').click(function(){
    let i = index-1;
    if(i<0) i=slides.length-1;
    showSlide(i);
});

/* THUMB CLICK */
thumbs.click(function(){
    showSlide($(this).data('index'));
});

/* LIGHTBOX */
$('.scx-pro-slide img').click(function(){
    $('.scx-lightbox-img').attr('src', $(this).data('full'));
    $('.scx-pro-lightbox').fadeIn(200);
});

$('.scx-pro-lightbox, .scx-lightbox-close').click(function(e){
    if(e.target !== this) return;
    $('.scx-pro-lightbox').fadeOut(200);
});

/* KEYBOARD */
$(document).keydown(function(e){
    if(e.key === 'ArrowRight') $('.scx-pro-next').click();
    if(e.key === 'ArrowLeft') $('.scx-pro-prev').click();
    if(e.key === 'Escape') $('.scx-pro-lightbox').fadeOut(200);
});

/* SWIPE */
let startX=0;

$('.scx-pro-main').on('touchstart', e=>{
    startX = e.originalEvent.touches[0].clientX;
});

$('.scx-pro-main').on('touchend', e=>{
    let endX = e.originalEvent.changedTouches[0].clientX;
    if(startX-endX>50) $('.scx-pro-next').click();
    if(endX-startX>50) $('.scx-pro-prev').click();
});

});
