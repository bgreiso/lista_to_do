document.addEventListener('DOMContentLoaded', function() {
    const expand_btn = document.querySelector(".expand-btn");
    const sidebar = document.querySelector(".sidebar");
    const mainContent = document.querySelector(".main-content");

    // Toggle sidebar collapse
    expand_btn.addEventListener("click", () => {
        document.body.classList.toggle("collapsed");
        localStorage.setItem('sidebarCollapsed', document.body.classList.contains("collapsed"));
        
        // Ajustar el margen del contenido principal
        if (document.body.classList.contains("collapsed")) {
            mainContent.style.marginLeft = '70px';
        } else {
            mainContent.style.marginLeft = '250px';
        }
    });

    // Cargar estado del sidebar
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        document.body.classList.add("collapsed");
        mainContent.style.marginLeft = '70px';
    }

    // Manejar colapsables de Bootstrap
    var collapseElements = document.querySelectorAll('[data-bs-toggle="collapse"]');
    collapseElements.forEach(function(element) {
        element.addEventListener('click', function() {
            if (document.body.classList.contains("collapsed")) {
                // Cerrar otros colapsables
                var openCollapses = document.querySelectorAll('.collapse.show');
                openCollapses.forEach(function(collapse) {
                    if (collapse.id !== this.getAttribute('data-bs-target').substring(1)) {
                        var bsCollapse = new bootstrap.Collapse(collapse, {
                            toggle: false
                        });
                        bsCollapse.hide();
                    }
                }.bind(this));
                
                // Abrir el colapsable actual
                var target = document.querySelector(this.getAttribute('data-bs-target'));
                var bsCollapse = new bootstrap.Collapse(target, {
                    toggle: true
                });
            }
        });
    });

    // Cerrar el sidebar cuando se hace clic fuera en móviles
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 768) {
            if (!sidebar.contains(e.target) && e.target !== expand_btn && !document.body.classList.contains("collapsed")) {
                document.body.classList.add("collapsed");
                mainContent.style.marginLeft = '70px';
            }
        }
    });
});