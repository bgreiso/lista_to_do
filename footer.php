</div>
        </div> 
    </div> 

    <!-- cierre de sesión -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">¿Listo para salir?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Selecciona "Cerrar sesión" si estás listo para finalizar tu sesión actual.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="cerrar_sesion.php" class="btn btn-primary">Cerrar sesión</a>
            </div>
        </div>
    </div>
</div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JavaScript -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Scripts personalizados -->
    <script src="admin.js"></script>
    
    <script>
    // Toggle del sidebar
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.body.classList.toggle('sidebar-toggled');
        document.querySelector('.sidebar').classList.toggle('toggled');
        
        if (document.querySelector('.sidebar').classList.contains('toggled')) {
            document.querySelector('.sidebar .collapse').classList.remove('show');
        }
    });
    
    // Cerrar el sidebar cuando se hace clic fuera en móviles
    window.addEventListener('DOMContentLoaded', function() {
        document.querySelector('body').addEventListener('click', function(e) {
            if (window.innerWidth < 768) {
                if (e.target.closest('.sidebar') === null && !e.target.closest('#sidebarToggle')) {
                    document.querySelector('.sidebar').classList.add('toggled');
                }
            }
        });
    });
    </script>
</body>
</html>