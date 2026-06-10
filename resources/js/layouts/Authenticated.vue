<template>
  <div class="flex h-screen bg-[#f8fafc] font-sans antialiased">
    <!-- SIDEBAR -->
    <aside class="sidebar-vm text-white flex flex-col shadow-2xl border-r border-slate-800">
      
      <!-- LOGO -->
      <div class="p-6 flex flex-col items-center justify-center logo-section">
        <div class="mb-2 p-3 bg-slate-800/30 rounded-2xl border border-slate-700/50 shadow-inner group transition-all duration-500 hover:border-indigo-500/50">
          <img 
            src="/logo.png"
            alt="EMI Logo" 
            class="h-10 w-auto object-contain group-hover:scale-105 transition-transform"
          />
        </div>
        <span class="text-[10px] font-bold text-indigo-300 uppercase tracking-widest mt-1">EmiSystem</span>
      </div>

      <!-- SELECTOR DE IDIOMA (Admin/Teacher) -->
      <div v-if="user.role === 'admin' || user.role === 'teacher'" class="px-4 py-2 mt-2">
        <div class="bg-slate-800/40 rounded-xl p-3 border border-slate-700/30 hover:border-indigo-500/30 transition-all duration-300">
          <label class="text-[9px] font-extrabold text-slate-500 uppercase tracking-tighter block mb-1">
            Departamento Activo
          </label>
          <div class="relative flex items-center">
            <select 
              v-model="selectedLangId" 
              @change="handleLanguageChange"
              class="w-full bg-transparent text-white text-xs font-semibold focus:ring-0 border-none cursor-pointer p-0 appearance-none pr-4"
            >
              <option v-for="lang in languages" :key="lang.id" :value="lang.id" class="bg-slate-900">
                {{ lang.name }}
              </option>
            </select>
            <span class="absolute right-0 text-[10px] pointer-events-none text-slate-500">▾</span>
          </div>
        </div>
      </div>

      <!-- NAVEGACIÓN PRINCIPAL -->
      <nav class="flex-1 px-4 space-y-2 mt-4 overflow-y-auto custom-scrollbar">
        
        <!-- Mostrar spinner mientras carga -->
        <div v-if="!user || !user.role" class="flex justify-center py-10">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>

        <!-- Mostrar menú solo cuando hay usuario -->
        <template v-else>
          
          <div class="section-header">Principal</div>
          <router-link 
            :to="{ name: user.role + '.dashboard' }" 
            class="nav-link-vm" 
            active-class="active"
          >
            <span class="flex items-center gap-3"><span class="icon">🏠</span> Dashboard</span>
          </router-link>

          <!-- ========== MENÚ ADMIN ========== -->
          <template v-if="user.role === 'admin'">
            
            <div class="section-header pt-4">👥 Usuarios</div>
            <div class="menu-group-vm">
              <button @click="toggleMenu('users')" class="nav-dropdown-btn-vm" :class="{ 'active-group': openMenus.users }">
                <span class="flex items-center gap-3"><span class="icon">👥</span> Gestión de Usuarios</span>
                <span class="arrow" :class="{ 'rotate': openMenus.users }">▾</span>
              </button>
              <transition name="expand">
                <div v-show="openMenus.users" class="submenu-vm">
                  <router-link :to="{ name: 'admin.pending-users' }" class="sub-nav-link-vm" active-class="active-sub">
                    Aspirantes Pendientes
                  </router-link>
                  <router-link :to="{ name: 'students.index' }" class="sub-nav-link-vm" active-class="active-sub">
                    Estudiantes
                  </router-link>
                  <router-link :to="{ name: 'admins.index' }" class="sub-nav-link-vm" active-class="active-sub">
                    Administradores
                  </router-link>
                </div>
              </transition>
            </div>

            <div class="section-header pt-4">📝 Exámenes</div>
            <div class="menu-group-vm">
              <button @click="toggleMenu('exams')" class="nav-dropdown-btn-vm" :class="{ 'active-group': openMenus.exams }">
                <span class="flex items-center gap-3"><span class="icon">📝</span> Gestión de Exámenes</span>
                <span class="arrow" :class="{ 'rotate': openMenus.exams }">▾</span>
              </button>
              <transition name="expand">
                <div v-show="openMenus.exams" class="submenu-vm">
                  <div class="submenu-label">💻 Computarizados</div>
                  <router-link :to="{ name: 'quiz.index' }" class="sub-nav-link-vm" active-class="active-sub">
                    Lista de Exámenes
                  </router-link>
                  <router-link :to="{ name: 'question.index' }" class="sub-nav-link-vm" active-class="active-sub">
                    Banco de Preguntas
                  </router-link>
                  <div class="submenu-divider"></div>
                  <div class="submenu-label">📦 Modular</div>
                  <router-link :to="{ name: 'admin.modules.index' }" class="sub-nav-link-vm" active-class="active-sub">
                    Gestión de Módulos
                  </router-link>
                  
                  <div class="submenu-divider"></div>
                  <div class="submenu-label">🗣️ Orales</div>
                  <router-link :to="{ name: 'admin.oral_questions.index' }" class="sub-nav-link-vm" active-class="active-sub">
                    Banco de Preguntas Orales
                  </router-link>
                </div>
              </transition>
            </div>

            <div class="section-header pt-4">📊 Reportes</div>
            <div class="menu-group-vm">
              <button @click="toggleMenu('reports')" class="nav-dropdown-btn-vm" :class="{ 'active-group': openMenus.reports }">
                <span class="flex items-center gap-3"><span class="icon">📊</span> Reportes Académicos</span>
                <span class="arrow" :class="{ 'rotate': openMenus.reports }">▾</span>
              </button>
              <transition name="expand">
                <div v-show="openMenus.reports" class="submenu-vm">
                  <router-link :to="{ name: 'admin.reports' }" class="sub-nav-link-vm" active-class="active-sub">
                    Resumen General
                  </router-link>
                  <router-link :to="{ name: 'admin.reports.computerized' }" class="sub-nav-link-vm" active-class="active-sub">
                    💻 Exámenes Computarizados
                  </router-link>
                  <router-link :to="{ name: 'admin.oral.reports' }" class="sub-nav-link-vm" active-class="active-sub">
                    🗣️ Exámenes Orales
                  </router-link>
                  <router-link :to="{ name: 'admin.modular.reports' }" class="sub-nav-link-vm" active-class="active-sub">
                    📦 Exámenes Modulares
                  </router-link>
                </div>
              </transition>
            </div>

            <!-- ========== ASIGNACIONES (con Usuarios Bloqueados) ========== -->
            <div class="section-header pt-4">🔑 Asignaciones</div>
            <div class="menu-group-vm">
              <button @click="toggleMenu('assignments')" class="nav-dropdown-btn-vm" :class="{ 'active-group': openMenus.assignments }">
                <span class="flex items-center gap-3"><span class="icon">🔑</span> Asignaciones</span>
                <span class="arrow" :class="{ 'rotate': openMenus.assignments }">▾</span>
              </button>
              <transition name="expand">
                <div v-show="openMenus.assignments" class="submenu-vm">
                  <router-link :to="{ name: 'assignments.index' }" class="sub-nav-link-vm" active-class="active-sub">
                    Asignación Individual
                  </router-link>
                  <router-link :to="{ name: 'admin.lab-control' }" class="sub-nav-link-vm" active-class="active-sub">
                    Habilitar Laboratorio
                  </router-link>
                  <router-link :to="{ name: 'assignments.slots-config' }" class="sub-nav-link-vm" active-class="active-sub">
                    ⚙️ Configurar Horarios
                  </router-link>
                  <router-link :to="{ name: 'assignments.special-slots' }" class="sub-nav-link-vm" active-class="active-sub">
                    ⭐ Horarios Especiales
                  </router-link>
                  
                  <!-- 🔥 NUEVA SECCIÓN: Usuarios Bloqueados -->
                  <div class="submenu-divider"></div>
                  <div class="submenu-label">🚫 BLOQUEOS</div>
                  <router-link :to="{ name: 'admin.blocked.users' }" class="sub-nav-link-vm" active-class="active-sub">
                    👤 Usuarios Bloqueados
                  </router-link>
                </div>
              </transition>
            </div>

            <!-- ========== HERRAMIENTAS ========== -->
            <div class="section-header pt-4">🛠️ Herramientas</div>
            <div class="menu-group-vm">
              <button @click="toggleMenu('tools')" class="nav-dropdown-btn-vm" :class="{ 'active-group': openMenus.tools }">
                <span class="flex items-center gap-3"><span class="icon">🛠️</span> Herramientas del Sistema</span>
                <span class="arrow" :class="{ 'rotate': openMenus.tools }">▾</span>
              </button>
              <transition name="expand">
                <div v-show="openMenus.tools" class="submenu-vm">
                  <div class="submenu-label">📱 CÓDIGOS QR</div>
                  <router-link :to="{ name: 'admin.qr-manager' }" class="sub-nav-link-vm" active-class="active-sub">
                    📲 Generador de QRs
                  </router-link>
                  <router-link :to="{ name: 'admin.qr-statistics' }" class="sub-nav-link-vm" active-class="active-sub">
                    📊 Estadísticas de QRs
                  </router-link>
                  <div class="submenu-divider"></div>
                  <div class="submenu-label">🔒 SEGURIDAD</div>
                  <button @click="handleSecurityLogs" class="sub-nav-link-vm" style="width: 100%; text-align: left;">
                    🔐 Registros de Seguridad
                  </button>
                  <router-link :to="{ name: 'admin.audit.logs' }" class="sub-nav-link-vm" active-class="active-sub">
                    📋 Auditoría de Actividad
                  </router-link>
                  <router-link :to="{ name: 'admin.security.reports' }" class="sub-nav-link-vm" active-class="active-sub">
                    🚨 Reporte de Violaciones
                  </router-link>
                  <router-link :to="{ name: 'admin.backups' }" class="sub-nav-link-vm" active-class="active-sub">
                    💾 Gestión de Backups
                  </router-link>
                </div>
              </transition>
            </div>
          </template>

          <!-- ========== MENÚ TEACHER ========== -->
          <template v-else-if="user.role === 'teacher'">
            <div class="section-header pt-4">📚 Académico</div>
            <router-link :to="{ name: 'teacher.results.index' }" class="nav-link-vm" active-class="active">
              <span class="flex items-center gap-3"><span class="icon">📜</span> Historial de Notas</span>
            </router-link>
          </template>

          <!-- ========== MENÚ STUDENT ========== -->
          <template v-else-if="user.role === 'student'">
            <div class="section-header pt-4">📊 Mis Resultados</div>
            <div class="menu-group-vm">
              <button @click="toggleMenu('stResults')" class="nav-dropdown-btn-vm" :class="{ 'active-group': openMenus.stResults }">
                <span class="flex items-center gap-3"><span class="icon">📈</span> Ver Resultados</span>
                <span class="arrow" :class="{ 'rotate': openMenus.stResults }">▾</span>
              </button>
              <transition name="expand">
                <div v-show="openMenus.stResults" class="submenu-vm">
                  <router-link :to="{ name: 'student.oral.index' }" class="sub-nav-link-vm" active-class="active-sub">
                    🗣️ Exámenes Orales
                  </router-link>
                  <router-link :to="{ name: 'student.history' }" class="sub-nav-link-vm" active-class="active-sub">
                    💻 Exámenes Computarizados
                  </router-link>
                  <router-link :to="{ name: 'student.modular.index' }" class="sub-nav-link-vm" active-class="active-sub">
                    📦 Exámenes Modulares
                  </router-link>
                </div>
              </transition>
            </div>

            <div class="section-header pt-4">⚠️ Inasistencias</div>
            <router-link :to="{ name: 'student.missed-exams' }" class="nav-link-vm" active-class="active">
              <span class="flex items-center gap-3"><span class="icon">📋</span> Ver Inasistencias</span>
            </router-link>

            <div class="section-header pt-4">📅 Acciones</div>
            <router-link :to="{ name: 'student.schedule' }" class="nav-link-vm" active-class="active">
              <span class="flex items-center gap-3"><span class="icon">📅</span> Programar Examen</span>
            </router-link>
          </template>
        </template>
      </nav>

      <!-- FOOTER -->
      <div class="p-4 border-t border-slate-800 bg-slate-950/50">
        <div v-if="user.role === 'student'" class="mb-3 px-2">
          <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest block mb-1">
            Idioma: {{ currentLanguageName }}
          </span>
        </div>

        <router-link :to="{ name: 'profile' }" class="profile-card group">
          <div class="relative">
            <img 
              :src="user.picture ? '/storage/' + user.picture : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name) + '&background=6366f1&color=fff'" 
              class="h-10 w-10 rounded-xl border border-indigo-500/30 object-cover transition-transform group-hover:scale-105"
              alt="User"
            />
            <div class="status-indicator"></div>
          </div>
          <div class="overflow-hidden">
            <p class="profile-label">Mi Cuenta</p>
            <p class="profile-name text-xs">{{ user.name }} {{ user.lastname }}</p>
          </div>
          <span class="ml-auto text-[10px] text-slate-500 group-hover:text-indigo-400 transition">✎</span>
        </router-link>

        <button @click="logout" class="logout-btn-vm group">
          <span class="icon group-hover:-translate-x-1 transition-transform text-sm">🚪</span> 
          <span class="text-sm">Cerrar Sesión</span>
        </button>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto">
      <!-- HEADER CON CAMPANARIO -->
      <div class="sticky top-0 z-10 bg-white/80 backdrop-blur-md border-b px-6 py-3 flex justify-end items-center gap-4">
        <component :is="NotificationComponent" v-if="NotificationComponent" />
        <router-link :to="{ name: 'profile' }" class="flex items-center gap-3 group md:hidden">
          <img 
            :src="user.picture ? '/storage/' + user.picture : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name) + '&background=6366f1&color=fff'" 
            class="h-8 w-8 rounded-full border-2 border-indigo-500/30 object-cover"
            alt="User"
          />
        </router-link>
      </div>
      
      <router-view v-slot="{ Component }">
        <transition name="fade-page" mode="out-in">
          <component :is="Component" v-if="Component" />
        </transition>
      </router-view>
    </main>
  </div>
</template>

<script setup>
import { reactive, watch, onMounted, ref, onUnmounted, shallowRef } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';
import StudentNotifications from '@/components/Notifications/StudentNotifications.vue';
import TeacherNotifications from '@/components/Notifications/TeacherNotifications.vue';
import AdminNotifications from '@/components/Notifications/AdminNotifications.vue';

const route = useRoute();
const router = useRouter();

// ========== ESTADO DEL USUARIO (desde localStorage) ==========
const user = ref(null);
const logout = () => {
    localStorage.removeItem('user_data');
    localStorage.removeItem('loggedIn');
    localStorage.removeItem('token');
    localStorage.removeItem('active_lang_id');
    router.push('/login');
};

// ========== CARGAR USUARIO DESDE LOCALSTORAGE ==========
const loadUserFromStorage = () => {
    const stored = localStorage.getItem('user_data');
    if (stored) {
        try {
            user.value = JSON.parse(stored);
            console.log('✅ Usuario cargado:', user.value?.role);
        } catch(e) {
            console.error('Error cargando usuario:', e);
            user.value = null;
        }
    } else {
        console.log('⚠️ No hay usuario en localStorage');
        user.value = null;
    }
};

// Cargar usuario inmediatamente
loadUserFromStorage();

// ========== COMPONENTE DE NOTIFICACIONES ==========
const NotificationComponent = shallowRef(null);

const updateNotificationComponent = () => {
    const userRole = user.value?.role;
    switch (userRole) {
        case 'student':
            NotificationComponent.value = StudentNotifications;
            break;
        case 'teacher':
            NotificationComponent.value = TeacherNotifications;
            break;
        case 'admin':
            NotificationComponent.value = AdminNotifications;
            break;
        default:
            NotificationComponent.value = null;
    }
};

updateNotificationComponent();

// ========== SISTEMA DE INACTIVIDAD ==========
let inactivityTimer = null;
let warningTimer = null;
const INACTIVITY_MINUTES = 20;
const INACTIVITY_MS = INACTIVITY_MINUTES * 60 * 1000;
const WARNING_MS = 60 * 1000;

const logoutByInactivity = async () => {
    clearInactivityTimers();
    logout();
};

const showInactivityWarning = () => {
    Swal.fire({
        title: '⚠️ Sesión por expirar',
        text: `Has estado inactivo por ${INACTIVITY_MINUTES} minutos. ¿Deseas continuar?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cerrar sesión',
        timer: 30000,
        timerProgressBar: true,
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            startInactivityTimer();
        } else {
            logoutByInactivity();
        }
    });
};

const startInactivityTimer = () => {
    clearInactivityTimers();
    warningTimer = setTimeout(showInactivityWarning, INACTIVITY_MS - WARNING_MS);
    inactivityTimer = setTimeout(logoutByInactivity, INACTIVITY_MS);
};

const clearInactivityTimers = () => {
    if (inactivityTimer) clearTimeout(inactivityTimer);
    if (warningTimer) clearTimeout(warningTimer);
    inactivityTimer = null;
    warningTimer = null;
};

const resetInactivityTimer = () => {
    clearInactivityTimers();
    startInactivityTimer();
};

const setupInactivityEvents = () => {
    const events = ['mousemove', 'mousedown', 'click', 'keydown', 'scroll', 'touchstart', 'wheel'];
    events.forEach(event => {
        window.addEventListener(event, resetInactivityTimer);
    });
};

const cleanupInactivityEvents = () => {
    const events = ['mousemove', 'mousedown', 'click', 'keydown', 'scroll', 'touchstart', 'wheel'];
    events.forEach(event => {
        window.removeEventListener(event, resetInactivityTimer);
    });
};

// --- LÓGICA DE IDIOMA ---
const languages = ref([]);
const selectedLangId = ref(localStorage.getItem('active_lang_id') || '1');
const currentLanguageName = ref('Cargando...');

const fetchLanguages = async () => {
    try {
        const response = await axios.get('/api/v1/languages');
        languages.value = response.data.data || response.data;
        const lang = languages.value.find(l => l.id == selectedLangId.value);
        currentLanguageName.value = lang ? lang.name : 'Inglés';
    } catch (error) {
        console.error("Error cargando idiomas:", error);
    }
};

const handleLanguageChange = () => {
    localStorage.setItem('active_lang_id', selectedLangId.value);
    window.location.reload();
};

// --- GESTIÓN DE LOGS DE SEGURIDAD ---
const handleSecurityLogs = () => {
    router.push({ name: 'admin.security-logs' });
};

// --- MENÚS ACORDEÓN ---
const openMenus = reactive({ 
    users: false, exams: false, reports: false,
    stResults: false, stAttendance: false, assignments: false, tools: false
});

const toggleMenu = (menu) => {
    Object.keys(openMenus).forEach(k => { if (k !== menu) openMenus[k] = false; });
    openMenus[menu] = !openMenus[menu];
};

watch(() => route.name, (name) => {
    if (!name) return;
    Object.keys(openMenus).forEach(k => openMenus[k] = false);
    if (name.includes('students') || name.includes('admins') || name.includes('pending-users')) openMenus.users = true;
    else if (name.includes('quiz') || name.includes('question') || name.includes('oral_questions')) openMenus.exams = true;
    else if (name.includes('report')) openMenus.reports = true;
    else if (name.includes('assignments') || name.includes('lab-control') || name.includes('slots')) openMenus.assignments = true;
    else if (name.includes('qr') || name.includes('security') || name.includes('audit') || name.includes('backups')) openMenus.tools = true;
    else if (name === 'student.oral.index' || name === 'student.history') openMenus.stResults = true;
    else if (route.query?.filter === 'missed') openMenus.stAttendance = true;
}, { immediate: true });

onMounted(async () => {
    loadUserFromStorage();
    await fetchLanguages();
    if (localStorage.getItem('loggedIn') === 'true') {
        setupInactivityEvents();
        startInactivityTimer();
    }
});

onUnmounted(() => {
    cleanupInactivityEvents();
    clearInactivityTimers();
});
</script>

<style scoped>
.sidebar-vm { width: 17rem; background-color: #0f172a; min-height: 100vh; position: sticky; top: 0; }
.logo-section { border-bottom: 1px solid rgba(51, 65, 85, 0.4); }
.section-header { padding: 0 0.75rem; font-size: 10px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.1em; }
.submenu-label { font-size: 8px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.25rem 0.75rem; margin-top: 0.25rem; }
.submenu-divider { height: 1px; background: rgba(51, 65, 85, 0.4); margin: 0.5rem 0.75rem; }

.nav-link-vm, .nav-dropdown-btn-vm {
    display: flex; align-items: center; justify-content: space-between;
    width: 100%; padding: 0.7rem 1rem; border-radius: 0.75rem;
    color: #94a3b8 !important; background: transparent; border: none;
    cursor: pointer; font-size: 0.9rem; transition: 0.2s;
}
.nav-link-vm:hover, .nav-dropdown-btn-vm:hover { background: rgba(30, 41, 59, 0.7); color: white !important; }
.nav-link-vm.active, .active-group { background: #4f46e5 !important; color: white !important; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }

.submenu-vm { margin: 0.25rem 0 0.5rem 1.75rem; border-left: 1px solid rgba(79, 70, 229, 0.3); padding-left: 0.5rem; display: flex; flex-direction: column; gap: 0.125rem; }
.sub-nav-link-vm { padding: 0.45rem 0.75rem; font-size: 0.85rem; color: #94a3b8; text-decoration: none; border-radius: 0.5rem; display: block; background: transparent; cursor: pointer; }
.sub-nav-link-vm:hover { color: white; background: rgba(30, 41, 59, 0.5); }
.sub-nav-link-vm.active-sub { color: #818cf8; font-weight: 600; background: rgba(79, 70, 229, 0.05); }

.profile-card { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(30, 41, 59, 0.4); border-radius: 1rem; text-decoration: none; }
.profile-label { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: 800; }
.profile-name { color: #f8fafc; font-weight: 600; }
.status-indicator { position: absolute; bottom: 2px; right: 2px; width: 10px; height: 10px; background: #10b981; border-radius: 50%; border: 2px solid #0f172a; }

.logout-btn-vm { display: flex; align-items: center; gap: 0.75rem; width: 100%; padding: 0.75rem; margin-top: 0.5rem; color: #f87171 !important; background: transparent; border: none; font-weight: 600; cursor: pointer; border-radius: 0.75rem; transition: 0.2s; }
.logout-btn-vm:hover { background: rgba(239, 68, 68, 0.1); color: #fecaca !important; }

.arrow { transition: transform 0.3s; font-size: 0.8rem; opacity: 0.5; }
.rotate { transform: rotate(180deg); }

.expand-enter-active, .expand-leave-active { transition: all 0.3s ease; max-height: 400px; overflow: hidden; }
.expand-enter-from, .expand-leave-to { opacity: 0; max-height: 0; }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #1e293b; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #4f46e5; border-radius: 10px; }

.fade-page-enter-active, .fade-page-leave-active { transition: opacity 0.2s ease; }
.fade-page-enter-from, .fade-page-leave-to { opacity: 0; }

.icon { display: inline-block; width: 1.25rem; text-align: center; }
</style>