document.addEventListener("DOMContentLoaded", function () {
    const splash = document.getElementById("splashScreen");

    const loginModal = document.getElementById("loginModal");
    const registerModal = document.getElementById("registerModal");

    const openLoginButtons = [
        document.getElementById("openLoginModal"),
        document.getElementById("openLoginModalNav")
    ];

    const openRegisterButtons = [
        document.getElementById("openRegisterModal")
    ];

    const closeLoginButton = document.getElementById("closeLoginModal");
    const closeRegisterButton = document.getElementById("closeRegisterModal");

    const switchToRegister = document.getElementById("switchToRegister");
    const switchToLogin = document.getElementById("switchToLogin");

    const toggleTheme = document.getElementById("toggleTheme");
    const header = document.querySelector(".main-header");

    const roleTabs = document.querySelectorAll(".role-tab");
    const rolePanels = document.querySelectorAll(".role-panel");
    const rolSeleccionado = document.getElementById("rolSeleccionado");

    const savedTheme = localStorage.getItem("theme");

    function setThemeButtonIcon(theme) {
        if (!toggleTheme) return;

        toggleTheme.innerHTML = theme === "dark"
            ? '<i class="bi bi-sun"></i>'
            : '<i class="bi bi-moon-stars"></i>';
    }

    if (savedTheme === "dark") {
        document.documentElement.setAttribute("data-theme", "dark");
        setThemeButtonIcon("dark");
    } else {
        document.documentElement.setAttribute("data-theme", "light");
        setThemeButtonIcon("light");
    }

    if (toggleTheme) {
        toggleTheme.addEventListener("click", function () {
            const currentTheme = document.documentElement.getAttribute("data-theme");

            if (currentTheme === "dark") {
                document.documentElement.setAttribute("data-theme", "light");
                localStorage.setItem("theme", "light");
                setThemeButtonIcon("light");
            } else {
                document.documentElement.setAttribute("data-theme", "dark");
                localStorage.setItem("theme", "dark");
                setThemeButtonIcon("dark");
            }
        });
    }

    if (splash) {
        setTimeout(() => {
            splash.classList.add("hide");
        }, 950);
    }

    openLoginButtons.forEach((button) => {
        if (button && loginModal) {
            button.addEventListener("click", function () {
                loginModal.classList.add("active");
            });
        }
    });

    openRegisterButtons.forEach((button) => {
        if (button && registerModal) {
            button.addEventListener("click", function () {
                registerModal.classList.add("active");
            });
        }
    });

    if (closeLoginButton && loginModal) {
        closeLoginButton.addEventListener("click", function () {
            loginModal.classList.remove("active");
        });
    }

    if (closeRegisterButton && registerModal) {
        closeRegisterButton.addEventListener("click", function () {
            registerModal.classList.remove("active");
        });
    }

    if (switchToRegister && loginModal && registerModal) {
        switchToRegister.addEventListener("click", function () {
            loginModal.classList.remove("active");
            registerModal.classList.add("active");
        });
    }

    if (switchToLogin && loginModal && registerModal) {
        switchToLogin.addEventListener("click", function () {
            registerModal.classList.remove("active");
            loginModal.classList.add("active");
        });
    }

    if (loginModal) {
        loginModal.addEventListener("click", function (e) {
            if (e.target === loginModal) {
                loginModal.classList.remove("active");
            }
        });
    }

    if (registerModal) {
        registerModal.addEventListener("click", function (e) {
            if (e.target === registerModal) {
                registerModal.classList.remove("active");
            }
        });
    }

    if (header) {
        window.addEventListener("scroll", function () {
            if (window.scrollY > 12) {
                header.classList.add("scrolled");
            } else {
                header.classList.remove("scrolled");
            }
        });
    }

    const revealElements = document.querySelectorAll("[data-reveal]");

    if (revealElements.length > 0) {
        const revealObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("visible");
                    }
                });
            },
            {
                threshold: 0.12
            }
        );

        revealElements.forEach((element) => {
            revealObserver.observe(element);
        });
    }

    if (roleTabs.length > 0) {
        roleTabs.forEach((tab) => {
            tab.addEventListener("click", function () {
                const selectedRole = this.getAttribute("data-role");

                roleTabs.forEach((item) => item.classList.remove("active"));
                this.classList.add("active");

                rolePanels.forEach((panel) => panel.classList.remove("active"));

                const activePanel = document.getElementById(`panel-${selectedRole}`);
                if (activePanel) {
                    activePanel.classList.add("active");
                }

                if (rolSeleccionado) {
                    rolSeleccionado.value = selectedRole;
                }
            });
        });
    }
});

if (window.edunexoSuccess) {
    Swal.fire({
        icon: "success",
        title: "Éxito",
        text: window.edunexoSuccess,
        confirmButtonColor: "#2748a6"
    });
}

if (window.edunexoError) {
    Swal.fire({
        icon: "error",
        title: "Error",
        text: window.edunexoError,
        confirmButtonColor: "#2748a6"
    });
}
