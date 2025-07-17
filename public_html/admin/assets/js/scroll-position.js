/**
 * Sistema de mantenimiento de posición de scroll
 * Compatible con todas las páginas administrativas
 */

class ScrollPositionManager {
    constructor(pageKey = null) {
        this.pageKey = pageKey || this.getPageKey();
        this.storageKey = `${this.pageKey}_scroll_position`;
        this.debounceTimeout = null;
        this.isNavigating = false;
        
        this.init();
    }
    
    // Obtener clave única de la página actual
    getPageKey() {
        const path = window.location.pathname;
        const filename = path.split('/').pop().replace('.php', '');
        return filename || 'admin';
    }
    
    // Inicializar el sistema
    init() {
        this.setupEventListeners();
        this.restoreScrollPosition();
    }
    
    // Guardar posición actual del scroll
    saveScrollPosition() {
        const scrollY = window.scrollY || window.pageYOffset;
        try {
            sessionStorage.setItem(this.storageKey, scrollY.toString());
        } catch (e) {
            console.warn('No se pudo guardar la posición del scroll:', e);
        }
    }
    
    // Restaurar posición guardada
    restoreScrollPosition() {
        try {
            const savedPosition = sessionStorage.getItem(this.storageKey);
            if (savedPosition !== null && !this.isNavigating) {
                const position = parseInt(savedPosition);
                
                // Esperar a que la página esté completamente cargada
                if (document.readyState === 'complete') {
                    this.scrollToPosition(position);
                } else {
                    window.addEventListener('load', () => {
                        this.scrollToPosition(position);
                    });
                }
            }
        } catch (e) {
            console.warn('No se pudo restaurar la posición del scroll:', e);
        }
    }
    
    // Scrollear a una posición específica
    scrollToPosition(position) {
        setTimeout(() => {
            window.scrollTo({
                top: position,
                behavior: 'auto' // Inmediato, no suave
            });
            // Limpiar la posición después de restaurarla
            this.clearSavedPosition();
        }, 50);
    }
    
    // Limpiar posición guardada
    clearSavedPosition() {
        try {
            sessionStorage.removeItem(this.storageKey);
        } catch (e) {
            console.warn('No se pudo limpiar la posición guardada:', e);
        }
    }
    
    // Configurar event listeners
    setupEventListeners() {
        // Guardar posición antes de navegar
        window.addEventListener('beforeunload', () => {
            if (!this.isNavigating && performance.navigation.type !== 1) {
                this.saveScrollPosition();
            }
        });
        
        // Manejar formularios
        this.setupFormHandlers();
        
        // Manejar enlaces de paginación
        this.setupPaginationHandlers();
        
        // Manejar ordenamiento de tablas
        this.setupSortingHandlers();
        
        // Manejar filtros y búsquedas
        this.setupFilterHandlers();
    }
    
    // Configurar manejadores de formularios
    setupFormHandlers() {
        const forms = document.querySelectorAll('form[method="GET"]');
        forms.forEach(form => {
            form.addEventListener('submit', () => {
                this.saveScrollPosition();
            });
        });
    }
    
    // Configurar manejadores de paginación
    setupPaginationHandlers() {
        const pageLinks = document.querySelectorAll('.page-btn, .pagination a, [href*="page="]');
        pageLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.handlePagination(link);
            });
        });
    }
    
    // Manejar navegación de páginas
    handlePagination(link) {
        this.saveScrollPosition();
        
        const url = new URL(link.href);
        const page = url.searchParams.get('page') || 1;
        
        this.goToPage(page);
    }
    
    // Ir a una página específica
    goToPage(page) {
        this.isNavigating = true;
        
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('page', page);
        
        window.location.search = urlParams.toString();
    }
    
    // Configurar manejadores de ordenamiento
    setupSortingHandlers() {
        // Buscar elementos de ordenamiento por diferentes selectores
        const sortElements = document.querySelectorAll(
            '[onclick*="sortTable"], .sortable, .sort-header, th[data-sort]'
        );
        
        sortElements.forEach(element => {
            // Si ya tiene onclick, interceptarlo
            if (element.getAttribute('onclick')) {
                const originalOnclick = element.getAttribute('onclick');
                element.removeAttribute('onclick');
                
                element.addEventListener('click', () => {
                    this.saveScrollPosition();
                    // Ejecutar la función original
                    eval(originalOnclick);
                });
            }
            
            // Si tiene data-sort, manejarlo
            if (element.hasAttribute('data-sort')) {
                element.addEventListener('click', () => {
                    this.handleSorting(element.getAttribute('data-sort'));
                });
            }
        });
    }
    
    // Manejar ordenamiento de columnas
    handleSorting(column) {
        this.saveScrollPosition();
        
        const urlParams = new URLSearchParams(window.location.search);
        const currentSort = urlParams.get('sort_by');
        const currentOrder = urlParams.get('sort_order');
        
        let newOrder = 'ASC';
        if (currentSort === column) {
            newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
        }
        
        urlParams.set('sort_by', column);
        urlParams.set('sort_order', newOrder);
        urlParams.delete('page'); // Reset a primera página al ordenar
        
        this.isNavigating = true;
        window.location.search = urlParams.toString();
    }
    
    // Configurar manejadores de filtros
    setupFilterHandlers() {
        // Selects de filtro
        const filterSelects = document.querySelectorAll(
            'select[name*="filter"], select[name*="search"], .filter-select'
        );
        
        filterSelects.forEach(select => {
            select.addEventListener('change', () => {
                this.saveScrollPosition();
                // Pequeño delay para permitir que se procese el cambio
                setTimeout(() => {
                    if (select.form) {
                        select.form.submit();
                    }
                }, 100);
            });
        });
        
        // Campos de búsqueda con debounce
        const searchInputs = document.querySelectorAll(
            'input[type="search"], input[name*="search"], .search-input'
        );
        
        searchInputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(this.debounceTimeout);
                this.debounceTimeout = setTimeout(() => {
                    this.saveScrollPosition();
                    if (input.form) {
                        input.form.submit();
                    }
                }, 500);
            });
        });
    }
    
    // Función global para ordenamiento (compatible con código existente)
    createSortFunction() {
        return (column) => {
            this.handleSorting(column);
        };
    }
    
    // Función global para paginación (compatible con código existente)
    createPageFunction() {
        return (page) => {
            this.goToPage(page);
        };
    }
    
    // Mostrar indicador de carga
    showLoadingIndicator() {
        const existingIndicator = document.getElementById('scroll-loading-indicator');
        if (existingIndicator) return;
        
        const indicator = document.createElement('div');
        indicator.id = 'scroll-loading-indicator';
        indicator.innerHTML = `
            <div style="
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: var(--bg-card);
                border: 1px solid var(--border-color);
                border-radius: 12px;
                padding: 2rem;
                z-index: 9999;
                display: flex;
                align-items: center;
                gap: 1rem;
                box-shadow: var(--shadow-lg);
            ">
                <div style="
                    width: 24px;
                    height: 24px;
                    border: 3px solid var(--border-color);
                    border-top-color: var(--accent-primary);
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                "></div>
                <span style="color: var(--text-primary); font-weight: 500;">Cargando...</span>
            </div>
        `;
        
        document.body.appendChild(indicator);
        
        // Auto-remover después de 3 segundos
        setTimeout(() => {
            const loadingEl = document.getElementById('scroll-loading-indicator');
            if (loadingEl) {
                loadingEl.remove();
            }
        }, 3000);
    }
    
    // Agregar CSS necesario
    addRequiredStyles() {
        if (document.getElementById('scroll-position-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'scroll-position-styles';
        style.textContent = `
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            
            .scroll-smooth {
                scroll-behavior: smooth;
            }
            
            .scroll-preserve {
                position: relative;
            }
            
            .scroll-preserve::after {
                content: '';
                position: absolute;
                top: -2px;
                left: 0;
                right: 0;
                height: 2px;
                background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .scroll-preserve.highlight::after {
                opacity: 1;
            }
        `;
        
        document.head.appendChild(style);
    }
}

// Inicializar automáticamente cuando se carga el DOM
document.addEventListener('DOMContentLoaded', function() {
    // Crear instancia global
    window.scrollManager = new ScrollPositionManager();
    
    // Agregar estilos necesarios
    window.scrollManager.addRequiredStyles();
    
    // Crear funciones globales para compatibilidad con código existente
    if (!window.sortTable) {
        window.sortTable = window.scrollManager.createSortFunction();
    }
    
    if (!window.goToPage) {
        window.goToPage = window.scrollManager.createPageFunction();
    }
    
    // Agregar clase para identificar elementos con scroll preservado
    const tableContainers = document.querySelectorAll('.table-container, .data-table, table');
    tableContainers.forEach(container => {
        container.classList.add('scroll-preserve');
    });
});

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ScrollPositionManager;
}
