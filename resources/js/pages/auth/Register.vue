<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 px-3 py-4 font-sans antialiased">
    <div class="max-w-4xl w-full bg-white rounded-2xl shadow-xl p-4 sm:p-6 md:p-8 border border-slate-100 relative overflow-hidden">
      
      <!-- Header -->
      <div class="text-center mb-6 sm:mb-8">
        <div class="inline-block p-3 bg-slate-50 rounded-xl mb-3 border border-slate-100 shadow-sm">
          <img src="/logo.png" alt="EMI Logo" class="h-10 sm:h-12 md:h-14 w-auto mx-auto object-contain" />
        </div>
        <h2 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-slate-800 tracking-tight">Solicitud de Registro</h2>
        <p class="text-slate-500 text-xs sm:text-sm mt-1 sm:mt-2">Gestión de identidad y validación Centro de Idiomas</p>
      </div>

      <form @submit.prevent="submitRegister" class="space-y-6 sm:space-y-8">
        <div class="flex flex-col lg:grid lg:grid-cols-2 gap-6 sm:gap-8 lg:gap-10">
          
          <!-- Sección Documentos -->
          <div class="space-y-4 sm:space-y-5">
            <h3 class="text-xs font-black text-indigo-500 uppercase tracking-widest ml-1">Documentación e Identidad</h3>
            
            <div v-for="(label, key) in documentFields" :key="key" class="flex flex-col">
              <label class="text-xs sm:text-sm font-semibold text-slate-700 mb-1.5 sm:mb-2 ml-1">{{ label }}</label>
              <div class="relative group">
                <div 
                  @click="openCamera(key)"
                  class="h-32 sm:h-36 md:h-40 w-full rounded-xl sm:rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 flex flex-col items-center justify-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50 transition-all overflow-hidden relative active:scale-95 touch-manipulation"
                >
                  <template v-if="previews[key]">
                    <img :src="previews[key]" class="h-full w-full object-cover" />
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                      <span class="text-white text-[10px] sm:text-xs font-bold bg-indigo-600 px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg">CAMBIAR FOTO</span>
                    </div>
                  </template>
                  <template v-else>
                    <div class="bg-white p-2 sm:p-3 rounded-full shadow-sm mb-1 sm:mb-2 group-hover:scale-110 transition-transform">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                    </div>
                    <span class="text-[8px] sm:text-[10px] font-bold text-slate-400 uppercase tracking-tight text-center px-2">Pulsar para capturar</span>
                  </template>
                </div>
              </div>
            </div>
          </div>

          <!-- Sección Información Académica -->
          <div class="space-y-4 sm:space-y-5">
            <h3 class="text-xs font-black text-indigo-500 uppercase tracking-widest ml-1">Información Académica</h3>
            
            <div class="space-y-3 sm:space-y-4">
              <input 
                v-model="form.name" 
                type="text" 
                placeholder="Nombres" 
                class="w-full px-4 sm:px-5 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl border border-slate-200 focus:border-indigo-500 outline-none transition-all text-sm font-medium touch-manipulation"
                required 
              />
              
              <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <input 
                  v-model="form.lastname" 
                  type="text" 
                  placeholder="Ap. Paterno" 
                  class="w-full px-3 sm:px-5 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl border border-slate-200 focus:border-indigo-500 outline-none transition-all text-sm font-medium touch-manipulation"
                  required 
                />
                <input 
                  v-model="form.surname" 
                  type="text" 
                  placeholder="Ap. Materno" 
                  class="w-full px-3 sm:px-5 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl border border-slate-200 focus:border-indigo-500 outline-none transition-all text-sm font-medium touch-manipulation"
                />
              </div>

              <div class="flex flex-col">
                <input 
                  v-model="form.email" 
                  type="email" 
                  placeholder="Correo Electrónico Institucional"
                  inputmode="email"
                  :class="[
                    'w-full px-4 sm:px-5 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl border outline-none transition-all text-sm font-medium touch-manipulation',
                    (!isEmailValid && form.email) ? 'border-red-500 bg-red-50' : 'border-slate-200 focus:border-indigo-500'
                  ]"
                  required
                />
                <p v-if="allowedDomain && allowedDomain !== '*'" 
                   :class="[
                     'text-[9px] sm:text-[10px] font-bold mt-1 sm:mt-2 ml-2 uppercase tracking-tight transition-colors',
                     (!isEmailValid && form.email) ? 'text-red-600' : 'text-slate-400'
                   ]">
                  {{ (!isEmailValid && form.email) ? '⚠ ' : 'ℹ ' }} Se requiere cuenta institucional (@{{ allowedDomain }})
                </p>
              </div>

              <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <input 
                  v-model="form.id_number" 
                  type="text" 
                  placeholder="C.I." 
                  inputmode="numeric"
                  class="w-full px-3 sm:px-5 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl border border-slate-200 focus:border-indigo-500 outline-none transition-all text-sm font-medium touch-manipulation"
                  required 
                />
                <input 
                  v-model="form.saga_code" 
                  type="text" 
                  placeholder="Código SAGA" 
                  class="w-full px-3 sm:px-5 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl border border-slate-200 focus:border-indigo-500 outline-none transition-all text-sm font-medium touch-manipulation"
                  required 
                />
              </div>

              <select 
                v-model="form.career_id" 
                class="w-full px-4 sm:px-5 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl border border-slate-200 focus:border-indigo-500 outline-none transition-all text-sm font-medium bg-white touch-manipulation"
                required
              >
                <option value="" disabled selected>Seleccione su Carrera</option>
                <option v-for="career in careers" :key="career.id" :value="career.id">{{ career.name }}</option>
              </select>

              <!-- Contraseñas -->
              <div class="space-y-3 sm:space-y-4">
                <div class="flex flex-col">
                  <input 
                    v-model="form.password" 
                    type="password" 
                    placeholder="Nueva Contraseña" 
                    :class="[
                      'w-full px-4 sm:px-5 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl border outline-none transition-all text-sm font-medium touch-manipulation',
                      (form.password && !isPasswordLongEnough) ? 'border-red-500 bg-red-50' : 'border-slate-200 focus:border-indigo-500'
                    ]" 
                    required 
                  />
                  
                  <p :class="[
                      'text-[9px] sm:text-[10px] font-bold mt-1 sm:mt-2 ml-2 uppercase tracking-tight transition-colors',
                      (form.password && !isPasswordLongEnough) ? 'text-red-600' : 'text-slate-400'
                    ]">
                    {{ (form.password && !isPasswordLongEnough) ? '⚠ Mínimo 8 caracteres' : 'ℹ Mínimo 8 caracteres' }}
                  </p>

                  <div v-if="form.password && isPasswordLongEnough" class="mt-1 sm:mt-2 px-1 sm:px-2">
                    <div class="flex flex-wrap justify-between items-center gap-1 mb-1">
                      <span class="text-[8px] sm:text-[9px] font-black uppercase text-slate-400">Seguridad:</span>
                      <span class="text-[8px] sm:text-[9px] font-black uppercase" :class="passwordStrength.textColor">{{ passwordStrength.label }}</span>
                    </div>
                    <div class="h-1 sm:h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                      <div :class="['h-full transition-all duration-500', passwordStrength.color]" :style="{ width: passwordStrength.score + '%' }"></div>
                    </div>
                  </div>
                </div>

                <div class="flex flex-col">
                  <input 
                    v-model="form.password_confirmation" 
                    type="password" 
                    placeholder="Confirmar Contraseña" 
                    :class="[
                      'w-full px-4 sm:px-5 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl border outline-none transition-all text-sm font-medium touch-manipulation',
                      (form.password_confirmation && !passwordsMatch) ? 'border-red-500' : 'border-slate-200 focus:border-indigo-500'
                    ]" 
                    required 
                  />
                  <p v-if="form.password_confirmation && !passwordsMatch" class="text-[9px] sm:text-[10px] text-red-600 font-bold mt-1 ml-2 uppercase">
                    Las contraseñas no coinciden
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Botón de envío -->
        <div class="pt-4 sm:pt-6 border-t border-slate-50">
          <button 
            :disabled="processing"
            type="submit"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 sm:py-4 rounded-xl sm:rounded-2xl transition-all shadow-lg shadow-indigo-100 flex items-center justify-center space-x-2 sm:space-x-3 disabled:opacity-50 active:scale-95 touch-manipulation"
          >
            <span v-if="processing" class="animate-spin border-2 border-white border-t-transparent rounded-full h-4 w-4 sm:h-5 sm:w-5"></span>
            <span class="text-sm sm:text-base">{{ processing ? 'PROCESANDO...' : 'ENVIAR SOLICITUD' }}</span>
          </button>
          <p class="text-center mt-5 sm:mt-6 text-xs sm:text-sm text-slate-500">
            ¿Ya tienes cuenta? 
            <router-link :to="{name: 'login'}" class="text-indigo-600 font-bold hover:underline active:text-indigo-700">
              Iniciar Sesión
            </router-link>
          </p>
        </div>
      </form>
    </div>

    <!-- Modal de Cámara optimizado para móvil -->
    <div v-if="showCamera" class="fixed inset-0 z-50 flex items-center justify-center bg-black p-0 sm:p-2 md:p-4">
      <div class="bg-white rounded-none sm:rounded-2xl md:rounded-3xl p-3 sm:p-4 md:p-6 max-w-lg w-full h-full sm:h-auto shadow-2xl flex flex-col">
        <div class="flex justify-between items-center mb-2 sm:mb-4 flex-shrink-0">
          <h3 class="font-bold text-slate-800 text-sm sm:text-base tracking-tight">
            📸 {{ documentFields[activeCameraType] }}
          </h3>
          <button @click="stopCamera" class="text-slate-400 hover:text-red-500 transition-colors p-2 active:scale-90">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <!-- Contenedor de video -->
        <div class="relative bg-black rounded-lg sm:rounded-xl overflow-hidden flex-grow" style="min-height: 0;">
          <video 
            ref="video" 
            autoplay 
            playsinline 
            class="w-full h-full object-cover"
            @loadedmetadata="adjustCropArea"
          ></video>
          
          <!-- Recuadro guía -->
          <div 
            ref="cropAreaRef"
            class="absolute border-4 border-indigo-500 shadow-lg z-10 transition-all duration-200"
            :class="activeCameraType === 'user_photo' ? 'rounded-full' : 'rounded-xl'"
            style="box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.6);"
          ></div>
          
          <!-- Texto de ayuda -->
          <div class="absolute bottom-3 left-0 right-0 text-center z-20">
            <div class="text-white text-xs sm:text-sm font-bold bg-black/60 backdrop-blur-sm py-2 px-3 rounded-full inline-block mx-auto">
              <span v-if="activeCameraType === 'user_photo'">🔘 Encuadra tu rostro en el círculo</span>
              <span v-else>🪪 Coloca tu carnet dentro del recuadro (8.5 x 5.4 cm)</span>
            </div>
          </div>
        </div>
        
        <div class="mt-3 sm:mt-4 flex gap-2 sm:gap-3 flex-shrink-0">
          <button 
            @click="takePhoto" 
            class="flex-1 bg-indigo-600 text-white font-bold py-3 sm:py-3.5 rounded-xl hover:bg-indigo-700 transition-all flex items-center justify-center gap-2 active:scale-95 touch-manipulation"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
              <path fill-rule="evenodd" d="M9 2a1 1 0 00-1 1v1H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2h-3V3a1 1 0 10-2 0v1H9V3a1 1 0 00-1-1z" />
            </svg>
            <span class="text-xs sm:text-sm font-bold">CAPTURAR</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useRouter } from 'vue-router'

const router = useRouter()
const processing = ref(false)
const careers = ref([])
const SITE_KEY = import.meta.env.VITE_RECAPTCHA_SITE_KEY
const allowedDomain = ref('')

const form = ref({
    name: '', lastname: '', surname: '', email: '',
    id_number: '', saga_code: '', career_id: '',
    password: '', password_confirmation: ''
})

const files = ref({ ci_front: null, ci_back: null, user_photo: null })
const previews = ref({ ci_front: null, ci_back: null, user_photo: null })

const documentFields = {
    ci_front: 'Carnet de Identidad (Anverso)',
    ci_back: 'Carnet de Identidad (Reverso)',
    user_photo: 'Fotografía del Postulante (Selfie)'
}

// Variables de cámara
const showCamera = ref(false)
const activeCameraType = ref(null)
const video = ref(null)
const stream = ref(null)
const cropAreaRef = ref(null)

// Proporción exacta del carnet: 8.5cm x 5.4cm = 1.574:1
const CARNET_ASPECT_RATIO = 8.5 / 5.4

// Validaciones
const isEmailValid = computed(() => {
    if (!form.value.email) return true
    if (allowedDomain.value === '*' || !allowedDomain.value) return true
    return form.value.email.endsWith(`@${allowedDomain.value}`)
})

const isPasswordLongEnough = computed(() => form.value.password.length >= 8)

const passwordsMatch = computed(() => {
    return form.value.password === form.value.password_confirmation && form.value.password !== ''
})

const passwordStrength = computed(() => {
    const pw = form.value.password
    if (!pw) return { score: 0, label: 'Esperando...', color: 'bg-slate-200', textColor: 'text-slate-400' }
    let score = 0
    if (pw.length >= 8) score++
    if (/[A-Z]/.test(pw)) score++
    if (/[0-9]/.test(pw)) score++
    if (/[^A-Za-z0-9]/.test(pw)) score++
    
    if (score <= 1) return { score: 25, label: 'Débil', color: 'bg-red-500', textColor: 'text-red-500' }
    if (score === 2) return { score: 50, label: 'Media', color: 'bg-yellow-500', textColor: 'text-yellow-600' }
    if (score === 3) return { score: 75, label: 'Fuerte', color: 'bg-green-500', textColor: 'text-green-600' }
    return { score: 100, label: 'Muy Segura', color: 'bg-indigo-600', textColor: 'text-indigo-600' }
})

// Lógica de cámara
const openCamera = async (type) => {
    activeCameraType.value = type
    showCamera.value = true
    
    await nextTick()
    
    try {
        const facingMode = type === 'user_photo' ? 'user' : 'environment'
        stream.value = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: { exact: facingMode } } 
        }).catch(() => {
            return navigator.mediaDevices.getUserMedia({ video: true })
        })
        
        if (video.value) {
            video.value.srcObject = stream.value
            video.value.onloadedmetadata = () => {
                adjustCropArea()
            }
        }
    } catch (e) {
        Swal.fire('Error', 'No se pudo acceder a la cámara. Verifica los permisos.', 'error')
        showCamera.value = false
    }
}

const adjustCropArea = () => {
    if (!cropAreaRef.value || !video.value) return
    
    const videoRect = video.value.getBoundingClientRect()
    const videoWidth = videoRect.width
    const videoHeight = videoRect.height
    
    if (activeCameraType.value === 'user_photo') {
        // Selfie cuadrado - 70% del tamaño del video
        const size = Math.min(videoWidth, videoHeight) * 0.7
        cropAreaRef.value.style.width = `${size}px`
        cropAreaRef.value.style.height = `${size}px`
    } else {
        // Documento con proporción exacta del carnet - 85% del ancho
        const targetWidth = videoWidth * 0.85
        const targetHeight = targetWidth / CARNET_ASPECT_RATIO
        
        if (targetHeight > videoHeight * 0.9) {
            const finalHeight = videoHeight * 0.8
            const finalWidth = finalHeight * CARNET_ASPECT_RATIO
            cropAreaRef.value.style.width = `${finalWidth}px`
            cropAreaRef.value.style.height = `${finalHeight}px`
        } else {
            cropAreaRef.value.style.width = `${targetWidth}px`
            cropAreaRef.value.style.height = `${targetHeight}px`
        }
    }
    
    cropAreaRef.value.style.left = '50%'
    cropAreaRef.value.style.top = '50%'
    cropAreaRef.value.style.transform = 'translate(-50%, -50%)'
}

const takePhoto = () => {
    if (!cropAreaRef.value || !video.value) return
    
    const videoRect = video.value.getBoundingClientRect()
    const cropRect = cropAreaRef.value.getBoundingClientRect()
    
    const scaleX = video.value.videoWidth / videoRect.width
    const scaleY = video.value.videoHeight / videoRect.height
    
    let cropX = (cropRect.left - videoRect.left) * scaleX
    let cropY = (cropRect.top - videoRect.top) * scaleY
    let cropWidth = cropRect.width * scaleX
    let cropHeight = cropRect.height * scaleY
    
    cropX = Math.max(0, cropX)
    cropY = Math.max(0, cropY)
    cropWidth = Math.min(cropWidth, video.value.videoWidth - cropX)
    cropHeight = Math.min(cropHeight, video.value.videoHeight - cropY)
    
    const canvas = document.createElement('canvas')
    canvas.width = cropWidth
    canvas.height = cropHeight
    
    const ctx = canvas.getContext('2d')
    ctx.drawImage(video.value, cropX, cropY, cropWidth, cropHeight, 0, 0, cropWidth, cropHeight)
    
    if (activeCameraType.value === 'user_photo') {
        const roundedCanvas = document.createElement('canvas')
        roundedCanvas.width = cropWidth
        roundedCanvas.height = cropHeight
        const roundedCtx = roundedCanvas.getContext('2d')
        
        roundedCtx.beginPath()
        roundedCtx.arc(cropWidth/2, cropHeight/2, cropWidth/2, 0, Math.PI * 2)
        roundedCtx.clip()
        roundedCtx.drawImage(canvas, 0, 0)
        
        roundedCanvas.toBlob((blob) => {
            savePhoto(blob)
        }, 'image/jpeg', 0.9)
    } else {
        canvas.toBlob((blob) => {
            savePhoto(blob)
        }, 'image/jpeg', 0.9)
    }
}

const savePhoto = (blob) => {
    files.value[activeCameraType.value] = blob
    previews.value[activeCameraType.value] = URL.createObjectURL(blob)
    stopCamera()
    
    Swal.fire({
        icon: 'success',
        title: '¡Foto capturada!',
        text: activeCameraType.value === 'user_photo' ? 'Selfie guardado' : 'Documento guardado',
        timer: 1200,
        showConfirmButton: false
    })
}

const stopCamera = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop())
        stream.value = null
    }
    showCamera.value = false
}

const handleResize = () => {
    if (showCamera.value) {
        adjustCropArea()
    }
}

// API
const fetchEmailSettings = async () => {
    try {
        const response = await axios.get('/api/v1/settings/email-domain')
        allowedDomain.value = response.data.domain
    } catch (e) {
        allowedDomain.value = 'adm.emi.edu.bo'
    }
}

const getCareers = async () => {
    try {
        const response = await axios.get('/api/v1/careers')
        careers.value = response.data
    } catch (e) { 
        console.error("Error cargando carreras") 
    }
}

const submitRegister = async () => {
    if (!isEmailValid.value) return Swal.fire('Correo Inválido', `Debe ser @${allowedDomain.value}`, 'warning')
    if (!isPasswordLongEnough.value) return Swal.fire('Contraseña', 'Mínimo 8 caracteres.', 'warning')
    if (!passwordsMatch.value) return Swal.fire('Error', 'Las contraseñas no coinciden.', 'warning')
    if (!files.value.ci_front) return Swal.fire('Faltan fotos', 'Debes capturar el CI (anverso).', 'error')
    if (!files.value.user_photo) return Swal.fire('Faltan fotos', 'Debes capturar tu selfie.', 'error')

    processing.value = true
    try {
        await axios.get('/sanctum/csrf-cookie')
        
        let token = ''
        if (SITE_KEY && window.grecaptcha) {
            token = await new Promise((resolve, reject) => {
                window.grecaptcha.ready(() => {
                    window.grecaptcha.execute(SITE_KEY, { action: 'register' }).then(resolve).catch(reject)
                })
            })
        }

        const fd = new FormData()
        Object.keys(form.value).forEach(k => fd.append(k, form.value[k]))
        Object.keys(files.value).forEach(k => {
            if(files.value[k]) fd.append(k, files.value[k], `${k}.jpg`)
        })
        if (token) fd.append('g-recaptcha-response', token)

        await axios.post('/api/v1/register', fd)
        Swal.fire('¡Éxito!', 'Registro enviado correctamente.', 'success').then(() => router.push('/login'))
    } catch (e) {
        const errorMsg = e.response?.data?.message || e.response?.data?.error || 'Error inesperado en el registro.'
        Swal.fire('Error', errorMsg, 'error')
    } finally { 
        processing.value = false 
    }
}

onMounted(async () => {
    await getCareers()
    await fetchEmailSettings()
    window.addEventListener('resize', handleResize)
    
    if (SITE_KEY) {
        const script = document.createElement('script')
        script.src = `https://www.google.com/recaptcha/api.js?render=${SITE_KEY}`
        script.async = true
        document.head.appendChild(script)
    }
})

onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
    if (stream.value) stopCamera()
    Object.values(previews.value).forEach(url => { 
        if (url) URL.revokeObjectURL(url) 
    })
})
</script>

<style scoped>
/* Optimizaciones para móvil */
@media (max-width: 640px) {
  input, select, button {
    font-size: 16px !important;
  }
  
  .touch-manipulation {
    touch-action: manipulation;
  }
}
</style>