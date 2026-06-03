import { ref, reactive, inject } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

// --- INITIALIZE AXIOS HEADERS IMMEDIATELY ---
const savedToken = localStorage.getItem('auth_token');
if (savedToken) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${savedToken}`;
}
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// --- Estado global reactivo ---
const user = reactive({
    name: "",
    lastname: "",
    email: "",
    role: "",
    isAdmin: false,
});

export default function useAuth() {
    const processing = ref(false);
    const validationErrors = ref({});
    const swal = inject("$swal");
    const router = useRouter();

    const loginForm = reactive({
        email: "",
        password: "",
        remember: false,
    });

    const loginUser = (data) => {
        const roleFromServer = data.role ? data.role.trim().toLowerCase() : 'student';

        user.name = data.name;
        user.lastname = data.lastname || ""; 
        user.email = data.email;
        user.picture = data.picture; // ✅ ADD THIS LINE
        user.role = roleFromServer; 
        user.isAdmin = (roleFromServer === 'admin');

        localStorage.setItem("loggedIn", JSON.stringify(true));
        localStorage.setItem("user_data", JSON.stringify(data));

        if (router.currentRoute.value.name === 'login') {
            let destination = "student.dashboard";
            if (user.role === 'admin') destination = "admin.dashboard";
            else if (user.role === 'teacher') destination = "teacher.dashboard";

            router.push({ name: destination });
        }
    };

    const submitLogin = async () => {
        if (processing.value) return;
        processing.value = true;
        validationErrors.value = {};

        try {
            await axios.get('/sanctum/csrf-cookie');
            const response = await axios.post('/api/v1/login', loginForm);
            const token = response.data.token;
            
            localStorage.setItem('auth_token', token);
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

            const userResponse = await axios.get('/api/v1/user');
            loginUser(userResponse.data);
        } catch (error) {
            if (error.response?.status === 422) {
                validationErrors.value = error.response.data.errors;
            }
            console.error("Error en el login:", error);
        } finally {
            processing.value = false;
        }
    };

    const logout = async () => {
        try {
            await axios.post("/api/v1/logout");
        } catch (error) {
            console.warn("La sesión ya había expirado");
        } finally {
            localStorage.clear();
            delete axios.defaults.headers.common['Authorization'];
            
            user.name = "";
            user.lastname = "";
            user.role = "";
            user.isAdmin = false;

            window.location.href = "/login";
        }
    };

    const getUser = async () => {
        const token = localStorage.getItem('auth_token');
        if (!token) return;

        try {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            const response = await axios.get("/api/v1/user");
            loginUser(response.data);
        } catch (error) {
            if (error.response?.status === 401) {
                localStorage.clear();
                window.location.href = "/login";
            }
        }
    };

    return {
        loginForm,
        validationErrors,
        processing,
        submitLogin,
        user,
        logout,
        getUser,
    };
}