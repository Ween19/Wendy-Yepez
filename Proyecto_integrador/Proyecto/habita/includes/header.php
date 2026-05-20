<script>
    document.addEventListener("DOMContentLoaded",()=>{

    const menuBtn=document.querySelector(".menu-toggle");
    const sidebar=document.getElementById("sidebar");
    const overlay=document.getElementById("sidebar-overlay");

    menuBtn.addEventListener("click",()=>{

    sidebar.classList.toggle("show");
    overlay.classList.toggle("show");

    });

    overlay.addEventListener("click",()=>{

    sidebar.classList.remove("show");
    overlay.classList.remove("show");

    });

});

</script>

<header class="topbar">
    <div class="topbar-left">

        <button class="menu-toggle" id="menuToggle">
            <i class="bi bi-list"></i>
        </button>

        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Buscar hábitos, categorías o registros...">
        </div>
    </div>

    <div class="topbar-user">
        <div class="user-avatar">
            <?= strtoupper(substr($_SESSION['nombre'],0,1)) ?>
        </div>

        <span class="user-name">
            <?= $_SESSION['nombre'] ?>
        </span>

    </div>

</header>