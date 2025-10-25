/**
 * Maneja la funcionalidad de pegar imágenes en el campo de carga de archivos
 */
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.querySelector('input[type="file"][name*="[imagenFile]"]');
    
    if (!fileInput) return;  // Salir si no se encuentra el input
    
    const formGroup = fileInput.closest('.form-group');
    
    // Crear un contenedor para la vista previa si no existe
    let previewContainer = formGroup.querySelector('.image-preview-container');
    if (!previewContainer) {
        previewContainer = document.createElement('div');
        previewContainer.className = 'image-preview-container mt-2';
        formGroup.appendChild(previewContainer);
    }

    // Función para mostrar la vista previa de la imagen
    function showImagePreview(file) {
        // Limpiar vista previa anterior
        previewContainer.innerHTML = '';
        
        // Crear y mostrar la imagen
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.style.maxWidth = '200px';
        img.className = 'img-thumbnail';
        previewContainer.appendChild(img);
        
        // Liberar memoria cuando ya no se necesite la URL
        img.onload = function() {
            URL.revokeObjectURL(img.src);
        };
    }

    // Manejar el evento de pegado (Ctrl+V)
    document.addEventListener('paste', function(e) {
        // Verificar si el portapapeles contiene imágenes
        const items = (e.clipboardData || window.clipboardData).items;
        
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                const blob = items[i].getAsFile();
                const fileName = `screenshot-${new Date().getTime()}.png`;
                
                // Crear un objeto FileList simulado
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(new File([blob], fileName, { type: 'image/png' }));
                
                // Asignar el archivo al input
                fileInput.files = dataTransfer.files;
                
                // Mostrar vista previa
                showImagePreview(blob);
                
                // Disparar evento change para notificar a otros scripts
                const event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
                
                // Evitar el comportamiento por defecto
                e.preventDefault();
                break;
            }
        }
    });

    // Manejar la vista previa cuando se selecciona un archivo manualmente
    fileInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            showImagePreview(this.files[0]);
        }
    });

    // Mostrar la imagen existente si hay una
    const existingImage = '{{ entity.imagen ? asset("uploads/notificaciones/" ~ entity.imagen) : "" }}';
    if (existingImage) {
        const img = document.createElement('img');
        img.src = existingImage;
        img.style.maxWidth = '200px';
        img.className = 'img-thumbnail';
        previewContainer.appendChild(img);
    }
});
