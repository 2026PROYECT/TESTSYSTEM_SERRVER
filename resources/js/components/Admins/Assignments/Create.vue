<template>
  <div class="min-h-screen bg-gray-50/50 py-10 px-4 font-sans">
    <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <!-- FORMULARIO PRINCIPAL -->
      <div class="lg:col-span-2">
        <form @submit.prevent="handleSave" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
          <div class="mb-8 flex justify-between items-center">
            <div class="flex items-center gap-4">
              <h2 class="text-2xl font-black text-gray-900 tracking-tight">Nueva Autorización</h2>
              
              <div :class="langColorClass" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border shadow-sm">
                <span class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-current animate-pulse"></span>
                  {{ currentLanguageName }}
                </span>
              </div>
            </div>

            <router-link :to="{ name: 'assignments.index' }" class="text-xs font-bold text-indigo-500 uppercase tracking-widest hover:underline">
              ← Volver al Listado
            </router-link>
          </div>

          <div class="space-y-6">
            <!-- Selección de Estudiante -->
            <div>
              <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Estudiante</label>
              
              <!-- Mostrar estado del estudiante si no es elegible -->
              <div v-if="selectedStudent && !eligibility.eligible" 
                   class="mb-4 p-4 rounded-2xl bg-red-50 border border-red-200">
                <div class="flex items-center gap-2 text-red-700">
                  <i class="fas fa-ban"></i>
                  <span class="text-xs font-bold uppercase">No disponible para programar</span>
                </div>
                <p class="text-sm text-red-600 mt-1">{{ eligibility.reason }}</p>
              </div>
              
              <!-- Estudiante seleccionado -->
              <div v-if="selectedStudent" class="mb-3 p-3 bg-indigo-50 rounded-2xl flex justify-between items-center border border-indigo-100">
                <div>
                  <span class="text-xs font-black text-indigo-700 uppercase">
                    {{ selectedStudent.name }} {{ selectedStudent.lastname }}
                  </span>
                  <div class="text-[9px] text-gray-500 mt-1">
                    {{ getStudentProgressText() }}
                  </div>
                </div>
                <button type="button" @click="resetSelection" class="text-indigo-400 hover:text-red-500 text-xl">×</button>
              </div>

              <!-- Buscador -->
              <input v-model="studentSearch" type="text" placeholder="Buscar estudiante por nombre..." 
                class="w-full bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 font-bold border-none" />
              
              <!-- Lista de estudiantes -->
              <div class="max-h-40 overflow-y-auto mt-2 bg-gray-50/50 rounded-2xl custom-scrollbar border border-gray-100">
                <div v-for="s in filteredStudents" :key="s.id" @click="selectStudent(s)"
                  class="p-3 cursor-pointer hover:bg-white text-sm font-bold border-b border-gray-100 flex justify-between items-center transition-colors">
                  <span>{{ s.name }} {{ s.lastname }}</span>
                  <div class="flex gap-1">
                    <span v-if="hasPassedOral(s.id)" class="text-[8px] bg-emerald-100 text-emerald-600 px-2 py-1 rounded-lg font-black">ORAL ✅</span>
                    <span v-if="hasPassedComp(s.id)" class="text-[8px] bg-blue-100 text-blue-600 px-2 py-1 rounded-lg font-black">COMP ✅</span>
                    <span v-if="hasPassedModular(s.id)" class="text-[8px] bg-purple-100 text-purple-600 px-2 py-1 rounded-lg font-black">MODULAR ✅</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Mostrar progreso del estudiante (si está seleccionado) -->
            <div v-if="selectedStudent && !isStudentFullyCertified && eligibility.eligible && !hasActiveBooking" 
                 class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-100">
              <div class="flex items-center justify-between mb-2">
                <span class="text-[10px] font-black text-gray-500 uppercase">Progreso académico</span>
                <span class="text-[9px] font-bold px-2 py-1 rounded-full bg-indigo-100 text-indigo-700">
                  {{ getCurrentTestLevel === 'oral' ? 'Pendiente Oral' : 
                     getCurrentTestLevel === 'secondary' ? 'Elegir camino' :
                     getCurrentTestLevel === 'comp' ? 'Pendiente Modular' :
                     getCurrentTestLevel === 'modular' ? 'Pendiente Comp' : 'Completado' }}
                </span>
              </div>
              <div class="space-y-2 text-xs">
                <div class="flex justify-between items-center">
                  <span>🗣️ Examen Oral</span>
                  <span :class="hasPassedOral(selectedStudent.id) ? 'text-emerald-600 font-bold' : 'text-amber-600'">
                    {{ hasPassedOral(selectedStudent.id) ? '✅ Aprobado' : '⏳ Pendiente' }}
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span>💻 Examen Computarizado</span>
                  <span :class="hasPassedComp(selectedStudent.id) ? 'text-emerald-600 font-bold' : 'text-gray-400'">
                    {{ hasPassedComp(selectedStudent.id) ? '✅ Aprobado' : 
                       (hasPassedModular(selectedStudent.id) ? '❌ No aplica (tomó Modular)' : '📌 Disponible') }}
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span>📦 Examen Modular</span>
                  <span :class="hasPassedModular(selectedStudent.id) ? 'text-emerald-600 font-bold' : 'text-gray-400'">
                    {{ hasPassedModular(selectedStudent.id) ? '✅ Aprobado' : 
                       (hasPassedComp(selectedStudent.id) ? '❌ No aplica (tomó Comp)' : '📌 Disponible') }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Cita activa pendiente -->
            <div v-if="selectedStudent && hasActiveBooking" class="p-4 bg-amber-50 rounded-2xl border border-amber-200">
              <div class="flex items-center gap-2 text-amber-700">
                <span class="text-xl">⏰</span>
                <span class="text-xs font-bold uppercase">Cita pendiente</span>
              </div>
              <p class="text-sm text-amber-600 mt-1">
                El estudiante tiene una cita activa. Debe completarla antes de programar una nueva.
              </p>
            </div>

            <!-- Estudiante completamente certificado -->
            <div v-if="selectedStudent && isStudentFullyCertified" class="p-4 bg-emerald-50 rounded-2xl text-center border border-emerald-200">
              <span class="block text-3xl">🎓</span>
              <span class="text-sm font-bold text-emerald-700">Estudiante Certificado</span>
              <p class="text-xs text-emerald-600 mt-1">
                {{ selectedStudent.name }} ha completado Oral + 
                {{ hasPassedComp(selectedStudent.id) ? 'Computarizado' : 'Modular' }}
              </p>
            </div>

            <!-- Fecha (solo si es elegible y no está certificado) -->
            <div v-if="selectedStudent && eligibility.eligible && !isStudentFullyCertified && !hasActiveBooking">
              <label class="block text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-2">Fecha Programada</label>
              <input type="date" v-model="selectedDate" :min="minDate" @change="onDateChange"
                class="w-full bg-gray-50 p-4 rounded-2xl focus:ring-2 focus:ring-indigo-400 font-bold border-none" />
            </div>

            <!-- Tipos de Examen (solo si es elegible y no está certificado) -->
            <div v-if="selectedStudent && eligibility.eligible && !isStudentFullyCertified && !hasActiveBooking && selectedDate" 
                 :class="[
                   'grid gap-4',
                   (canTakeCompTest && canTakeModularTest) ? 'grid-cols-2' : 
                   (canTakeCompTest || canTakeModularTest) ? 'grid-cols-1 max-w-xs mx-auto' : 
                   'grid-cols-1'
                 ]">
              
              <!-- 1. ORAL TEST -->
              <div v-if="!hasPassedOral(selectedStudent.id)" 
                   @click="setTestType('OralTest')" 
                   :class="[
                     form.test_type === 'OralTest' ? 'border-amber-500 bg-amber-50 ring-2 ring-amber-200' : 'border-gray-100',
                     'cursor-pointer'
                   ]"
                   class="p-4 rounded-2xl border-2 text-center transition-all relative">
                
                <span class="block text-2xl">🗣️</span>
                <span class="text-[10px] font-black uppercase text-amber-600">Oral Test</span>
                <span class="text-[8px] text-gray-400 block mt-1">Cada 30 min | 1 cupo</span>
                <span class="text-[9px] font-bold text-amber-600 mt-2 block">📌 Próximo paso obligatorio</span>
              </div>

              <!-- 2. COMP TEST -->
              <div v-if="canTakeCompTest || (hasPassedOral(selectedStudent?.id) && !hasPassedComp(selectedStudent?.id) && !hasPassedModular(selectedStudent?.id))" 
                   @click="setTestType('CompTest')" 
                   :class="[
                     form.test_type === 'CompTest' ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200' : 'border-gray-100',
                     canTakeCompTest ? 'cursor-pointer' : 'opacity-40 cursor-not-allowed grayscale'
                   ]"
                   class="p-4 rounded-2xl border-2 text-center transition-all relative">
                
                <!-- Bloqueo por ya tener Modular -->
                <div v-if="!canTakeCompTest && hasPassedOral(selectedStudent?.id) && !hasPassedComp(selectedStudent?.id) && hasPassedModular(selectedStudent?.id)" 
                     class="absolute inset-0 flex items-center justify-center bg-white/95 rounded-2xl z-10">
                  <span class="text-[9px] font-black text-center px-2">✅ YA COMPLETÓ MODULAR</span>
                </div>
                
                <!-- Bloqueo por ya tener Comp -->
                <div v-if="hasPassedComp(selectedStudent?.id)" 
                     class="absolute inset-0 flex items-center justify-center bg-white/95 rounded-2xl z-10">
                  <span class="text-[9px] font-black text-center px-2 text-emerald-600">✅ YA APROBADO</span>
                </div>

                <span class="block text-2xl">💻</span>
                <span class="text-[10px] font-black uppercase text-indigo-600">Comp Test</span>
                <span class="text-[8px] text-gray-400 block mt-1">Cada hora | 4 cupos</span>
                <span v-if="canTakeCompTest" class="text-[9px] font-bold text-indigo-600 mt-2 block">✓ Disponible</span>
              </div>

              <!-- 3. MODULAR TEST (mismo comportamiento que CompTest) -->
              <div v-if="canTakeModularTest || (hasPassedOral(selectedStudent?.id) && !hasPassedModular(selectedStudent?.id) && !hasPassedComp(selectedStudent?.id))" 
                   @click="setTestType('ModularTest')" 
                   :class="[
                     form.test_type === 'ModularTest' ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-200' : 'border-gray-100',
                     canTakeModularTest ? 'cursor-pointer' : 'opacity-40 cursor-not-allowed grayscale'
                   ]"
                   class="p-4 rounded-2xl border-2 text-center transition-all relative">
                
                <!-- Bloqueo por ya tener Comp -->
                <div v-if="!canTakeModularTest && hasPassedOral(selectedStudent?.id) && !hasPassedModular(selectedStudent?.id) && hasPassedComp(selectedStudent?.id)" 
                     class="absolute inset-0 flex items-center justify-center bg-white/95 rounded-2xl z-10">
                  <span class="text-[9px] font-black text-center px-2">✅ YA COMPLETÓ COMP</span>
                </div>
                
                <!-- Bloqueo por ya tener Modular -->
                <div v-if="hasPassedModular(selectedStudent?.id)" 
                     class="absolute inset-0 flex items-center justify-center bg-white/95 rounded-2xl z-10">
                  <span class="text-[9px] font-black text-center px-2 text-emerald-600">✅ YA APROBADO</span>
                </div>

                <span class="block text-2xl">📦</span>
                <span class="text-[10px] font-black uppercase text-emerald-600">Modular Test</span>
                <span class="text-[8px] text-gray-400 block mt-1">Cada hora | 4 cupos</span>
                <span v-if="canTakeModularTest" class="text-[9px] font-bold text-emerald-600 mt-2 block">✓ Disponible</span>
              </div>
            </div>

            <!-- Selector de Horario -->
            <div v-if="selectedDate && form.test_type && eligibility.eligible && !isStudentFullyCertified && !hasActiveBooking && selectedStudent">
              <div v-if="loadingSlots" class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <span class="ml-2 text-sm text-gray-500">Cargando horarios...</span>
              </div>
              
              <div v-else>
                <label class="block text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-2">
                  Horarios Disponibles - {{ getSlotDescription() }}
                </label>
                
                <div v-if="availableSlots.length === 0" class="text-center py-8 text-gray-400 text-sm bg-gray-50 rounded-2xl">
                  No hay horarios disponibles para esta fecha
                </div>
                
                <div v-else class="grid grid-cols-4 gap-2 p-4 bg-gray-50 rounded-2xl border border-gray-100 max-h-60 overflow-y-auto">
                  <button
                    v-for="slot in availableSlots"
                    :key="slot.time"
                    type="button"
                    @click="selectTimeSlot(slot)"
                    :disabled="!slot.is_available"
                    :class="[
                      'py-3 rounded-xl text-center font-bold transition-all',
                      selectedTimeSlot === slot.time
                        ? 'bg-indigo-600 text-white ring-2 ring-indigo-300'
                        : slot.is_available
                          ? 'bg-white hover:bg-indigo-100 text-gray-700 border border-gray-200 cursor-pointer'
                          : 'bg-gray-200 text-gray-400 cursor-not-allowed opacity-50'
                    ]"
                    :title="getSlotTooltip(slot)">
                    <div class="text-sm font-bold">{{ slot.label }}</div>
                    <div class="text-[9px] mt-1">
                      <span v-if="slot.is_available" class="text-green-600">
                        {{ slot.available }}/{{ slot.capacity }} libre
                      </span>
                      <span v-else-if="slot.occupied >= slot.capacity" class="text-red-500">
                        ❌ Completo
                      </span>
                      <span v-else-if="slot.is_past" class="text-gray-400">
                        ⏰ Pasado
                      </span>
                    </div>
                  </button>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                  💡 <strong>OralTest:</strong> 1 cupo cada 30 minutos | 
                  <strong>CompTest / ModularTest:</strong> 4 cupos por hora
                </p>
              </div>
            </div>
          </div>

<button type="submit" 
  :disabled="isLoading || !form.student_id || !selectedDate || !selectedTimeSlot || isStudentFullyCertified || !eligibility.eligible || hasActiveBooking || !form.test_type" 
  class="w-full mt-8 bg-indigo-600 text-white py-4 rounded-2xl font-black hover:bg-indigo-700 disabled:opacity-30 shadow-xl shadow-indigo-200 transition-all active:scale-95">
  <span v-if="isStudentFullyCertified">🎓 ESTUDIANTE CERTIFICADO</span>
  <span v-else-if="!eligibility.eligible || hasActiveBooking">⛔ ESTUDIANTE NO DISPONIBLE</span>
  <span v-else-if="!form.test_type">📚 SELECCIONE UN TIPO DE EXAMEN</span>
  <span v-else>{{ isLoading ? 'Procesando...' : 'Confirmar Autorización' }}</span>
</button>
        </form>
      </div>

      <!-- SLOTS OCUPADOS -->
      <div class="lg:col-span-1">
        <div class="bg-gray-900 text-white p-6 rounded-3xl shadow-xl h-full border border-gray-800">
          <h3 class="text-xs font-black uppercase mb-6 text-gray-400 flex justify-between items-center">
            <span>Slots Ocupados</span>
            <span class="bg-gray-800 px-2 py-1 rounded text-[10px]">{{ occupiedSlotsForSelectedDate.length }} total</span>
          </h3>

          <div v-if="occupiedSlotsForSelectedDate.length === 0" class="text-center py-10 text-gray-500 text-xs">
            No hay exámenes programados para esta fecha
          </div>

          <div class="space-y-3 overflow-y-auto max-h-[600px] pr-2 custom-scrollbar">
            <div v-for="slot in occupiedSlotsForSelectedDate" :key="slot.id" 
              :class="slot.test_type === 'OralTest' ? 'border-l-4 border-l-amber-500 bg-amber-500/5' : (slot.test_type === 'ModularTest' ? 'border-l-4 border-l-emerald-500 bg-emerald-500/5' : 'border-l-4 border-l-indigo-500 bg-indigo-500/5')"
              class="bg-gray-800/40 p-4 rounded-xl border border-gray-700/50 transition-all hover:bg-gray-800">
              
              <div class="flex justify-between items-start mb-2">
                <div :class="getTypeBadgeClass(slot.test_type)" 
                     class="flex items-center gap-1.5 px-2 py-1 rounded-lg text-[9px] font-black uppercase tracking-tighter">
                  <span>{{ getTypeIcon(slot.test_type) }}</span>
                  <span>{{ getTypeLabel(slot.test_type) }}</span>
                </div>

                <div class="text-right">
                  <div class="text-[10px] font-black text-gray-400 uppercase">{{ formatDate(slot.start_at) }}</div>
                  <div class="text-[9px] font-medium text-gray-500">{{ formatTimeOnly(slot.start_at) }}</div>
                </div>
              </div>

              <div class="text-xs font-bold text-white uppercase tracking-tight truncate">
                {{ slot.student?.name }} {{ slot.student?.lastname }}
              </div>

              <div v-if="slot.passed" class="mt-2 flex items-center gap-1 text-[8px] font-black text-emerald-400 uppercase">
                <span class="w-1 h-1 bg-emerald-400 rounded-full animate-pulse"></span>
                Aprobado / Completado
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

// ==================== CONFIGURACIÓN ====================
const API_BASE_URL = '/api/v1';

// ==================== ESTADO ====================
const students = ref([]);
const allAssignments = ref([]);
const studentSearch = ref('');
const selectedStudent = ref(null);
const isLoading = ref(false);
const loadingSlots = ref(false);
const selectedDate = ref(new Date(Date.now() + 86400000).toISOString().split('T')[0]);
const selectedTimeSlot = ref('');
const availableSlots = ref([]);
const languages = ref([]);
const eligibility = ref({ eligible: true, reason: '' });

const form = ref({
  student_id: '',
  active: 1,
  test_type: '',
  start_at: null
});

// ==================== COMPUTED ====================
const minDate = computed(() => new Date(Date.now() + 86400000).toISOString().split('T')[0]);

const filteredStudents = computed(() => {
  if (!studentSearch.value) return students.value;
  const searchLower = studentSearch.value.toLowerCase();
  return students.value.filter(s => 
    `${s.name} ${s.lastname || ''}`.toLowerCase().includes(searchLower)
  );
});

const occupiedSlotsForSelectedDate = computed(() => {
  if (!selectedDate.value || !allAssignments.value.length) return [];
  return allAssignments.value.filter(assignment => {
    if (!assignment.start_at) return false;
    const assignmentDate = new Date(assignment.start_at).toISOString().split('T')[0];
    return assignmentDate === selectedDate.value;
  });
});

const currentLanguageName = computed(() => {
  const langId = localStorage.getItem('active_lang_id') || 1;
  if (languages.value.length === 0) return langId == 1 ? 'Inglés' : 'Cargando...';
  const lang = languages.value.find(l => Number(l.id) === Number(langId));
  return lang ? lang.name : 'Inglés';
});

const langColorClass = computed(() => {
  const name = currentLanguageName.value.toLowerCase();
  if (name.includes('ing')) return 'bg-blue-100 text-blue-700 border-blue-200';
  if (name.includes('fra')) return 'bg-rose-100 text-rose-700 border-rose-200';
  if (name.includes('por')) return 'bg-emerald-100 text-emerald-700 border-emerald-200';
  return 'bg-gray-100 text-gray-700 border-gray-200';
});

// Estados de aprobación
const hasPassedOral = (studentId) => {
  if (!studentId) return false;
  return allAssignments.value.some(slot => 
    slot.student_id === studentId && slot.test_type === 'OralTest' && slot.passed == 1
  );
};

const hasPassedComp = (studentId) => {
  if (!studentId) return false;
  return allAssignments.value.some(slot => 
    slot.student_id === studentId && slot.test_type === 'CompTest' && slot.passed == 1
  );
};

const hasPassedModular = (studentId) => {
  if (!studentId) return false;
  return allAssignments.value.some(slot => 
    slot.student_id === studentId && slot.test_type === 'ModularTest' && slot.passed == 1
  );
};

// Cita activa pendiente
const hasActiveBooking = computed(() => {
  if (!selectedStudent.value) return false;
  return allAssignments.value.some(a => 
    a.student_id === selectedStudent.value.id && 
    a.active === 1
  );
});

// Nivel actual del estudiante
const getCurrentTestLevel = computed(() => {
  if (!selectedStudent.value) return 'blocked';
  
  const hasOral = hasPassedOral(selectedStudent.value.id);
  const hasComp = hasPassedComp(selectedStudent.value.id);
  const hasModular = hasPassedModular(selectedStudent.value.id);
  
  if (!hasOral) return 'oral';
  if (!hasComp && !hasModular) return 'secondary';
  if (hasComp && !hasModular) return 'modular';
  if (!hasComp && hasModular) return 'comp';
  return 'completed';
});

// Disponibilidad de exámenes
const canTakeCompTest = computed(() => {
  if (!selectedStudent.value) return false;
  const hasOral = hasPassedOral(selectedStudent.value.id);
  const hasComp = hasPassedComp(selectedStudent.value.id);
  const hasModular = hasPassedModular(selectedStudent.value.id);
  
  return hasOral && !hasComp && !hasModular;
});

const canTakeModularTest = computed(() => {
  if (!selectedStudent.value) return false;
  const hasOral = hasPassedOral(selectedStudent.value.id);
  const hasComp = hasPassedComp(selectedStudent.value.id);
  const hasModular = hasPassedModular(selectedStudent.value.id);
  
  return hasOral && !hasModular && !hasComp;
});

// Estudiante completamente certificado
const isStudentFullyCertified = computed(() => {
  if (!selectedStudent.value) return false;
  const hasOral = hasPassedOral(selectedStudent.value.id);
  const hasComp = hasPassedComp(selectedStudent.value.id);
  const hasModular = hasPassedModular(selectedStudent.value.id);
  
  return hasOral && (hasComp || hasModular);
});

// ==================== FUNCIONES AUXILIARES ====================
const getStudentProgressText = () => {
  if (!selectedStudent.value) return '';
  const hasOral = hasPassedOral(selectedStudent.value.id);
  const hasComp = hasPassedComp(selectedStudent.value.id);
  const hasModular = hasPassedModular(selectedStudent.value.id);
  
  if (!hasOral) return '📌 Pendiente: Examen Oral';
  if (!hasComp && !hasModular) return '📌 Elegir: Computarizado o Modular';
  if (hasComp && !hasModular) return '📌 Pendiente: Examen Modular';
  if (!hasComp && hasModular) return '📌 Pendiente: Examen Computarizado';
  return '🎓 Certificado completo';
};

const getTypeLabel = (type) => {
  const labels = { 'OralTest': 'Oral', 'CompTest': 'Comp', 'ModularTest': 'Modular' };
  return labels[type] || type;
};

const getTypeIcon = (type) => {
  const icons = { 'OralTest': '🗣️', 'CompTest': '💻', 'ModularTest': '📦' };
  return icons[type] || '📋';
};

const getTypeBadgeClass = (type) => {
  const classes = {
    'OralTest': 'text-amber-400 bg-amber-400/10',
    'CompTest': 'text-indigo-400 bg-indigo-400/10',
    'ModularTest': 'text-emerald-400 bg-emerald-400/10'
  };
  return classes[type] || 'text-gray-400 bg-gray-400/10';
};

const getSlotDescription = () => {
  if (form.value.test_type === 'OralTest') return 'Cada 30 min (1 cupo)';
  return 'Cada hora (4 cupos)';
};

// ==================== SLOTS ====================
const loadAvailableSlots = async () => {
  if (!selectedDate.value || !form.value.test_type || !selectedStudent.value) {
    availableSlots.value = [];
    return;
  }
  
  loadingSlots.value = true;
  
  try {
    // Para ModularTest, usar la misma lógica que CompTest
    let testTypeForApi = form.value.test_type;
    if (testTypeForApi === 'ModularTest') {
      testTypeForApi = 'CompTest';
    }
    
    const response = await axios.get('/api/v1/admin/get-available-slots', {
      params: {
        date: selectedDate.value,
        test_type: testTypeForApi,
        language_id: localStorage.getItem('active_lang_id') || 1
      }
    });
    
    if (response.data.status === 'success') {
      availableSlots.value = response.data.slots;
    } else {
      availableSlots.value = [];
    }
    
  } catch (error) {
    console.error('Error loading available slots:', error);
    availableSlots.value = [];
  } finally {
    loadingSlots.value = false;
  }
};

const selectTimeSlot = (slot) => {
  if (slot.is_available) {
    selectedTimeSlot.value = slot.time;
    form.value.start_at = `${selectedDate.value} ${slot.time}`;
  }
};

const getSlotTooltip = (slot) => {
  if (slot.is_past) return 'Horario ya pasado';
  if (slot.blocked) return 'Horario bloqueado';
  if (slot.occupied >= slot.capacity) return `Cupo completo (${slot.occupied}/${slot.capacity})`;
  return `${slot.available} cupo(s) disponible(s)`;
};

const onDateChange = () => {
  selectedTimeSlot.value = '';
  loadAvailableSlots();
};

// ==================== SET TEST TYPE ====================
const setTestType = async (type) => {
  // Validaciones
  if (type === 'CompTest' && !canTakeCompTest.value) {
    if (hasPassedComp(selectedStudent.value?.id)) {
      Swal.fire({ icon: 'warning', title: 'Ya aprobado', text: 'El estudiante ya aprobó el examen Computarizado.', confirmButtonColor: '#4f46e5' });
    } else if (hasPassedModular(selectedStudent.value?.id)) {
      Swal.fire({ icon: 'warning', title: 'Camino completado', text: 'El estudiante ya completó el examen Modular. Debe tomar el examen Computarizado.', confirmButtonColor: '#4f46e5' });
    } else if (!hasPassedOral(selectedStudent.value?.id)) {
      Swal.fire({ icon: 'warning', title: 'Requisito no cumplido', text: 'El estudiante debe aprobar el examen Oral primero.', confirmButtonColor: '#4f46e5' });
    }
    return;
  }
  
  if (type === 'ModularTest' && !canTakeModularTest.value) {
    if (hasPassedModular(selectedStudent.value?.id)) {
      Swal.fire({ icon: 'warning', title: 'Ya aprobado', text: 'El estudiante ya aprobó el examen Modular.', confirmButtonColor: '#4f46e5' });
    } else if (hasPassedComp(selectedStudent.value?.id)) {
      Swal.fire({ icon: 'warning', title: 'Camino completado', text: 'El estudiante ya completó el examen Computarizado. Debe tomar el examen Modular.', confirmButtonColor: '#4f46e5' });
    } else if (!hasPassedOral(selectedStudent.value?.id)) {
      Swal.fire({ icon: 'warning', title: 'Requisito no cumplido', text: 'El estudiante debe aprobar el examen Oral primero.', confirmButtonColor: '#4f46e5' });
    }
    return;
  }
  
  form.value.test_type = type;
  selectedTimeSlot.value = '';
  await loadAvailableSlots();
};

// ==================== VERIFICAR ELEGIBILIDAD ====================
const checkStudentEligibility = async (studentId) => {
  try {
    const response = await axios.get(`/api/v1/admin/check-student-eligibility/${studentId}`);
    eligibility.value = response.data;
    
    if (eligibility.value.sancion_until) {
      const fechaFin = new Date(eligibility.value.sancion_until).toLocaleDateString();
      eligibility.value.reason = `Estudiante suspendido hasta el ${fechaFin} por reprobación previa.`;
      eligibility.value.eligible = false;
    }
    
    if (hasActiveBooking.value) {
      eligibility.value.eligible = false;
      eligibility.value.reason = 'El estudiante tiene una cita pendiente. Debe completarla antes de programar una nueva.';
    }
    
    return eligibility.value.eligible;
  } catch (error) {
    eligibility.value = { eligible: true, reason: '' };
    return true;
  }
};

// ==================== ACCIONES ====================
const selectStudent = async (s) => {
  selectedStudent.value = s;
  form.value.student_id = s.id;
  
  await checkStudentEligibility(s.id);
  
  const hasOral = hasPassedOral(s.id);
  const hasComp = hasPassedComp(s.id);
  const hasModular = hasPassedModular(s.id);
  
  if (!hasOral) {
    form.value.test_type = 'OralTest';
  } else if (hasComp && hasModular) {
    form.value.test_type = '';
    Swal.fire({
      icon: 'success',
      title: 'Estudiante Certificado',
      text: `${s.name} ${s.lastname} ya completó su proceso.`,
      confirmButtonColor: '#4f46e5'
    });
  } else if (!hasComp && !hasModular) {
    form.value.test_type = '';
  } else if (!hasComp && hasModular) {
    form.value.test_type = 'CompTest';
  } else if (hasComp && !hasModular) {
    form.value.test_type = 'ModularTest';
  }
  
  selectedTimeSlot.value = '';
  await loadAvailableSlots();
};

const resetSelection = () => {
  selectedStudent.value = null;
  form.value.student_id = '';
  form.value.test_type = '';
  studentSearch.value = '';
  selectedTimeSlot.value = '';
  availableSlots.value = [];
  eligibility.value = { eligible: true, reason: '' };
};

const handleSave = async () => {
  if (isStudentFullyCertified.value) {
    Swal.fire('Error', 'Este estudiante ya está certificado', 'error');
    return;
  }
  
  if (!eligibility.value.eligible || hasActiveBooking.value) {
    Swal.fire('Error', eligibility.value.reason || 'Estudiante no disponible', 'error');
    return;
  }
  
  if (!selectedDate.value || !selectedTimeSlot.value) {
    Swal.fire('Error', 'Seleccione una fecha y horario', 'error');
    return;
  }
  
  if (!form.value.test_type) {
    Swal.fire('Error', 'Seleccione un tipo de examen', 'error');
    return;
  }
  
  // Validar que el slot aún tenga cupo
  const selectedSlot = availableSlots.value.find(s => s.time === selectedTimeSlot.value);
  if (!selectedSlot || !selectedSlot.is_available) {
    Swal.fire('Error', 'El horario seleccionado ya no está disponible. Por favor, seleccione otro.', 'error');
    await loadAvailableSlots();
    return;
  }

  isLoading.value = true;
  try {
    const startDateTime = `${selectedDate.value} ${selectedTimeSlot.value}`;
    
    const payload = {
      student_id: form.value.student_id,
      test_type: form.value.test_type,
      start_at: startDateTime,
      language_id: localStorage.getItem('active_lang_id') ? parseInt(localStorage.getItem('active_lang_id')) : 1
    };

    const response = await axios.post(`${API_BASE_URL}/admin/create-assignment`, payload);
    
    if (response.data.status === 'success') {
      Swal.fire({ 
        icon: 'success', 
        title: '¡Autorizado!', 
        text: 'Asignación creada correctamente.',
        showConfirmButton: false, 
        timer: 2000 
      });
      
      resetSelection();
      await refreshData();
    } else {
      throw new Error(response.data.message);
    }

  } catch (error) {
    const errorMsg = error.response?.data?.message || error.response?.data?.error || 'No se pudo guardar la autorización.';
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: errorMsg,
      confirmButtonColor: '#4f46e5'
    });
  } finally {
    isLoading.value = false;
  }
};
const refreshData = async () => {
  try {
    const activeLangId = localStorage.getItem('active_lang_id') || 1;
    
    const [sRes, aRes, lRes] = await Promise.all([
      axios.get(`${API_BASE_URL}/students`),
      axios.get(`${API_BASE_URL}/quiz-assignments`, {
        params: { language_id: activeLangId }
      }),
      axios.get(`${API_BASE_URL}/languages`)
    ]);
    
    students.value = sRes.data?.data || (Array.isArray(sRes.data) ? sRes.data : []);
    allAssignments.value = aRes.data?.data || [];
    languages.value = lRes.data || [];
    
  } catch (err) {
    console.error("Error al cargar datos", err);
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '---';
  const d = new Date(dateString.replace(' ', 'T'));
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
};

const formatTimeOnly = (dateString) => {
  if (!dateString) return '--:--';
  const d = new Date(dateString.replace(' ', 'T'));
  return d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', hour12: false });
};

// ==================== WATCHERS ====================
watch([selectedDate, () => form.value.test_type, () => selectedStudent.value], async () => {
  if (selectedStudent.value && form.value.test_type && !isStudentFullyCertified.value && eligibility.eligible && !hasActiveBooking.value) {
    selectedTimeSlot.value = '';
    await loadAvailableSlots();
  }
});

// ==================== LIFECYCLE ====================
onMounted(async () => {
  if (!localStorage.getItem('active_lang_id')) {
    localStorage.setItem('active_lang_id', '1');
  }
  await refreshData();
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>