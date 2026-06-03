import "./bootstrap";
import { createApp } from "vue";
import router from "./routes/index";
import VueSweetalert2 from "vue-sweetalert2";
import App from "./App.vue";
import axios from "axios";
import '../css/tailwind.css';

// --- CONFIGURACIÓN GLOBAL DE AXIOS ---
const token = localStorage.getItem('auth_token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}
axios.defaults.headers.common['Accept'] = 'application/json';

// Interceptor para detectar si el token expira (401) mientras navegamos
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            localStorage.clear();
            window.location.href = "/login"; // Redirección limpia
        }
        return Promise.reject(error);
    }
);

createApp(App)
  .use(router)
  .use(VueSweetalert2)
  .mount("#app");