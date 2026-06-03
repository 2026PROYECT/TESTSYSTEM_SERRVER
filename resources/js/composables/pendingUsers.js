// composables/pendingUsers.js
import { ref } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

export default function usePendingUsers() {
    const pendingUsers = ref([])
    const isLoading = ref(false)

    const getPendingUsers = async () => {
        isLoading.value = true
        try {
            // 🔥 CAMBIAR: usar 'auth_token' en lugar de 'token'
            const token = localStorage.getItem('auth_token')
            
            console.log('Token obtenido:', token ? 'Sí existe' : 'NO EXISTE')
            
            if (!token) {
                Swal.fire({
                    title: 'Sesión no encontrada',
                    text: 'Por favor, inicia sesión nuevamente',
                    icon: 'warning',
                    confirmButtonText: 'Ir al login'
                }).then(() => {
                    window.location.href = '/login'
                })
                return
            }
            
            const response = await axios.get('/api/v1/admin/pending-users', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            })
            
            pendingUsers.value = response.data.data || response.data
            
        } catch (error) {
            console.error('Error:', error)
            if (error.response?.status === 401) {
                Swal.fire('Sesión expirada', 'Por favor, inicia sesión nuevamente', 'error')
                    .then(() => {
                        localStorage.clear()
                        window.location.href = '/login'
                    })
            } else {
                Swal.fire('Error', 'No se pudieron cargar los aspirantes', 'error')
            }
        } finally {
            isLoading.value = false
        }
    }

    const approveUser = async (id) => {
        const result = await Swal.fire({
            title: '¿Aprobar aspirante?',
            text: 'El estudiante será movido a la lista oficial.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, aprobar',
            cancelButtonText: 'Cancelar'
        })
        
        if (result.isConfirmed) {
            try {
                const token = localStorage.getItem('auth_token')
                await axios.post(`/api/v1/admin/pending-users/${id}/approve`, {}, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                })
                Swal.fire('Aprobado', 'El aspirante ha sido aprobado', 'success')
                await getPendingUsers()
            } catch (error) {
                Swal.fire('Error', 'No se pudo aprobar al usuario', 'error')
            }
        }
    }

    const rejectUser = async (id) => {
        const result = await Swal.fire({
            title: '¿Rechazar solicitud?',
            text: 'Los datos se eliminarán permanentemente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, rechazar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33'
        })
        
        if (result.isConfirmed) {
            try {
                const token = localStorage.getItem('auth_token')
                await axios.delete(`/api/v1/admin/pending-users/${id}/reject`, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                })
                Swal.fire('Rechazado', 'La solicitud ha sido rechazada', 'success')
                await getPendingUsers()
            } catch (error) {
                Swal.fire('Error', 'No se pudo rechazar la solicitud', 'error')
            }
        }
    }

    return {
        pendingUsers,
        isLoading,
        getPendingUsers,
        approveUser,
        rejectUser
    }
}