<template>
  <div class="min-h-screen grid grid-cols-1 lg:grid-cols-2 bg-slate-50 overflow-hidden">
    
    <div class="relative hidden lg:flex flex-col justify-between p-12 bg-gray-950 text-white overflow-hidden">
      <div class="absolute inset-0 z-0">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-indigo-600 rounded-full blur-[160px] opacity-40"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-pink-500 rounded-full blur-[160px] opacity-30"></div>
      </div>

      <div class="relative z-10 flex items-center gap-3">
        <img 
          src="/logo.png"
          alt="EmiSystem Logo" 
          class="h-10 w-auto object-contain drop-shadow-md"
        />
        <span class="text-2xl font-bold tracking-tight">Emi<span class="text-indigo-400">System</span></span>
      </div>

      <div class="relative z-10 max-w-lg mb-12">
        <h1 class="text-6xl font-extrabold leading-tight tracking-tighter mb-6 bg-gradient-to-r from-white via-slate-200 to-indigo-300 bg-clip-text text-transparent">
          Master the Language, Unlock the World.
        </h1>
        <p class="text-xl text-slate-300/90 leading-relaxed font-light">
          The all-in-one platform for English proficiency testing. Assess skills, track progress, and break language barriers with our modern evaluation system.
        </p>
      </div>

      <div class="relative z-10 text-sm text-slate-500">
        &copy; 2026 EmiSystem Platform. All rights reserved.
      </div>
    </div>

    <div class="flex items-center justify-center p-6 sm:p-12 lg:p-24 bg-white">
      
      <div class="w-full max-w-md">
        
        <div class="flex flex-col items-center mb-12 lg:items-start lg:hidden">
            <img 
              src="/logo.png"
              alt="EmiSystem Logo" 
              class="h-14 w-auto mb-6 drop-shadow-lg shadow-indigo-200"
            />
            <h2 class="text-3xl font-extrabold text-gray-950 tracking-tighter text-center">Welcome Back!</h2>
            <p class="text-gray-600 mt-2 text-center">Sign in to access your dashboard.</p>
        </div>

        <div class="hidden lg:block mb-12">
            <h2 class="text-4xl font-extrabold text-gray-950 tracking-tighter">Welcome back!</h2>
            <p class="text-lg text-gray-600 mt-3">Please enter your credentials to continue.</p>
        </div>

        <form @submit.prevent="submitLogin" class="space-y-6">
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-500 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
              </div>
              <input
                v-model="loginForm.email"
                type="email"
                placeholder="john.doe@emisystem.com"
                class="w-full pl-13 pr-5 py-4 bg-white border border-gray-200 rounded-2xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all duration-300 outline-none placeholder:text-gray-400 shadow-sm"
                required
              />
            </div>
            <Transition name="fade-slide">
              <div v-if="validationErrors?.email" class="mt-2.5 text-xs text-rose-600 font-medium px-1 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" />
                </svg>
                {{ validationErrors.email[0] }}
              </div>
            </Transition>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
            <div class="relative group">
              <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-500 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
              </div>
              <input
                v-model="loginForm.password"
                type="password"
                placeholder="••••••••••••"
                class="w-full pl-13 pr-5 py-4 bg-white border border-gray-200 rounded-2xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all duration-300 outline-none placeholder:text-gray-400 shadow-sm"
                required
              />
            </div>
            <Transition name="fade-slide">
              <div v-if="validationErrors?.password" class="mt-2.5 text-xs text-rose-600 font-medium px-1 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" />
                </svg>
                {{ validationErrors.password[0] }}
              </div>
            </Transition>
          </div>

          <div class="flex items-center justify-between">
            <label class="flex items-center cursor-pointer group">
              <input 
                type="checkbox" 
                v-model="loginForm.remember"
                class="w-4.5 h-4.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-200 transition-all cursor-pointer"
              />
              <span class="ml-2.5 text-sm text-gray-600 group-hover:text-gray-800 transition-colors">Remember me</span>
            </label>
            <div class="flex justify-end mt-2">
  <router-link 
    :to="{ name: 'ForgotPassword' }" 
    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors"
  >
    ¿Olvidaste tu contraseña?
  </router-link>
</div>
<div class="mt-4 text-center">
    <p class="text-sm text-slate-500">
        ¿Eres un nuevo estudiante? 
        <router-link :to="{ name: 'register' }" class="text-indigo-600 font-bold">
    Regístrate aquí
</router-link>
    </p>
</div>
          </div>

          <button
            :disabled="processing"
            class="relative w-full py-4 bg-gray-950 hover:bg-gray-800 text-white font-bold rounded-2xl shadow-lg shadow-slate-200 hover:shadow-slate-300 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 disabled:opacity-60 disabled:transform-none overflow-hidden group"
          >
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:animate-shimmer"></div>

            <span v-if="!processing" class="relative z-10">Sign In</span>
            <span v-else class="relative z-10 flex justify-center">
              <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import useAuth from "@/composables/auth";
// No hay cambios en la lógica, el composable funciona exactamente igual.
const { loginForm, validationErrors, processing, submitLogin } = useAuth();
</script>

<style scoped>
/* Transición suave para los mensajes de error */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-5px);
}

/* Animación de Shimmer para el botón (opcional, requiere extender config de Tailwind) */
@keyframes shimmer {
  100% {
    transform: translateX(100%);
  }
}
.group:hover .group-hover\:animate-shimmer {
  animation: shimmer 1.5s infinite;
}

/* Clase de utilidad para el padding del input con icono */
.pl-13 {
    padding-left: 3.25rem; /* 52px */
}
</style>