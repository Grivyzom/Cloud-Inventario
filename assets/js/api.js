// assets/js/api.js - Funciones para interactuar con la API PHP

class CloudInventarioAPI {
    constructor() {
        this.baseURL = 'api/products.php';
    }

    // Realizar petición HTTP
    async request(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // Obtener todos los productos
    async getProducts() {
        return await this.request(this.baseURL);
    }

    // Obtener un producto por ID
    async getProduct(id) {
        return await this.request(`${this.baseURL}?id=${id}`);
    }

    // Buscar productos
    async searchProducts(query) {
        return await this.request(`${this.baseURL}?search=${encodeURIComponent(query)}`);
    }

    // Obtener productos por categoría
    async getProductsByCategory(category) {
        return await this.request(`${this.baseURL}?category=${encodeURIComponent(category)}`);
    }

    // Obtener estadísticas
    async getStats() {
        return await this.request(`${this.baseURL}?stats=1`);
    }

    // Crear producto
    async createProduct(productData) {
        return await this.request(this.baseURL, {
            method: 'POST',
            body: JSON.stringify(productData)
        });
    }

    // Actualizar producto
    async updateProduct(productData) {
        return await this.request(this.baseURL, {
            method: 'PUT',
            body: JSON.stringify(productData)
        });
    }

    // Eliminar producto
    async deleteProduct(id) {
        return await this.request(this.baseURL, {
            method: 'DELETE',
            body: JSON.stringify({ id: id })
        });
    }
}

// Función para manejar errores de la API
function handleAPIError(error, fallbackMessage = 'Error en la operación') {
    console.error('API Error:', error);
    
    let message = fallbackMessage;
    
    if (error.message.includes('fetch')) {
        message = 'Error de conexión. Verifique que el servidor esté funcionando.';
    } else if (error.message.includes('404')) {
        message = 'Recurso no encontrado.';
    } else if (error.message.includes('500')) {
        message = 'Error interno del servidor.';
    }
    
    return message;
}

// Función para probar la conexión de la API
async function testAPIConnection() {
    try {
        const api = new CloudInventarioAPI();
        const response = await api.getProducts();
        console.log('✅ API Connection successful:', response);
        return true;
    } catch (error) {
        console.error('❌ API Connection failed:', error);
        return false;
    }
}

// Exportar para uso global
window.CloudInventarioAPI = CloudInventarioAPI;
window.handleAPIError = handleAPIError;
window.testAPIConnection = testAPIConnection;