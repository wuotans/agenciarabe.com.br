// Variáveis globais
let draggedTask = null;
let dragTimeout = null;

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    inicializarModais();
    inicializarDragAndDrop();
    carregarTarefasViaAjax(); // Recarregar a cada 30 segundos
    setInterval(carregarTarefasViaAjax, 30000);
});

// ========== MODAIS ==========
function inicializarModais() {
    const modal = document.getElementById('taskModal');
    const btnAddTarefa = document.getElementById('btnAddTarefa');
    const closeBtn = document.querySelector('.close');
    const taskForm = document.getElementById('taskForm');
    
    // Abrir modal nova tarefa
    if (btnAddTarefa) {
        btnAddTarefa.onclick = function() {
            document.getElementById('modalTitle').innerText = '➕ Nova Tarefa';
            taskForm.reset();
            document.getElementById('taskId').value = '';
            document.getElementById('columnId').value = '';
            modal.style.display = 'block';
        }
    }
    
    // Abrir modal para coluna específica
    document.querySelectorAll('.btn-add-task-to-column').forEach(btn => {
        btn.onclick = function() {
            const columnId = this.dataset.columnId;
            document.getElementById('columnId').value = columnId;
            document.getElementById('modalTitle').innerText = '➕ Nova Tarefa';
            taskForm.reset();
            document.getElementById('taskId').value = '';
            modal.style.display = 'block';
        }
    });
    
    // Fechar modal
    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }
    }
    
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    
    // Submit do formulário via AJAX
    if (taskForm) {
        taskForm.onsubmit = function(e) {
            e.preventDefault();
            salvarTarefaViaAjax();
        }
    }
    
    // Botões de editar
    document.querySelectorAll('.btn-edit-task').forEach(btn => {
        btn.onclick = function() {
            const task = JSON.parse(this.dataset.task);
            document.getElementById('taskId').value = task.id;
            document.getElementById('titulo').value = task.titulo;
            document.getElementById('descricao').value = task.descricao;
            document.getElementById('prioridade').value = task.prioridade;
            document.getElementById('prazo').value = task.prazo;
            document.getElementById('responsavel_id').value = task.responsavel_id || '';
            document.getElementById('columnId').value = task.coluna_id;
            document.getElementById('modalTitle').innerText = '✏️ Editar Tarefa';
            modal.style.display = 'block';
        }
    });
    
    // Botões de excluir
    document.querySelectorAll('.btn-delete-task').forEach(btn => {
        btn.onclick = function() {
            if (confirm('Tem certeza que deseja excluir esta tarefa?')) {
                excluirTarefaViaAjax(this.dataset.taskId, this);
            }
        }
    });
}

// ========== CRUD VIA AJAX ==========
function salvarTarefaViaAjax() {
    const taskId = document.getElementById('taskId').value;
    const acao = taskId ? 'editar' : 'criar';
    
    const formData = new FormData();
    formData.append('acao', acao);
    if (taskId) formData.append('id', taskId);
    formData.append('coluna_id', document.getElementById('columnId').value);
    formData.append('titulo', document.getElementById('titulo').value);
    formData.append('descricao', document.getElementById('descricao').value);
    formData.append('prioridade', document.getElementById('prioridade').value);
    formData.append('prazo', document.getElementById('prazo').value);
    formData.append('responsavel_id', document.getElementById('responsavel_id').value);
    
    fetch('api_tarefas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacao(data.message, 'success');
            document.getElementById('taskModal').style.display = 'none';
            carregarTarefasViaAjax(); // Recarregar as tarefas
        } else {
            mostrarNotificacao(data.message, 'error');
        }
    })
    .catch(error => {
        mostrarNotificacao('Erro ao salvar tarefa', 'error');
    });
}

function excluirTarefaViaAjax(taskId, elemento) {
    const formData = new FormData();
    formData.append('acao', 'excluir');
    formData.append('id', taskId);
    
    fetch('api_tarefas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Animação de fade out
            const taskCard = elemento.closest('.task-card');
            taskCard.style.transition = 'all 0.3s';
            taskCard.style.opacity = '0';
            taskCard.style.transform = 'translateX(-20px)';
            setTimeout(() => {
                taskCard.remove();
                atualizarContagemTarefas();
                mostrarNotificacao(data.message, 'success');
            }, 300);
        } else {
            mostrarNotificacao(data.message, 'error');
        }
    })
    .catch(error => {
        mostrarNotificacao('Erro ao excluir tarefa', 'error');
    });
}

function carregarTarefasViaAjax() {
    fetch('api_tarefas.php?acao=get_dados')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                atualizarBoard(data.colunas);
            }
        })
        .catch(error => console.error('Erro ao carregar:', error));
}

// ========== DRAG AND DROP ==========
function inicializarDragAndDrop() {
    const tasks = document.querySelectorAll('.task-card');
    const columns = document.querySelectorAll('.task-list');
    
    // Adicionar listeners para as tarefas
    tasks.forEach(task => {
        task.setAttribute('draggable', true);
        task.addEventListener('dragstart', handleDragStart);
        task.addEventListener('dragend', handleDragEnd);
    });
    
    // Adicionar listeners para as colunas
    columns.forEach(column => {
        column.addEventListener('dragover', handleDragOver);
        column.addEventListener('dragleave', handleDragLeave);
        column.addEventListener('drop', handleDrop);
    });
}

function handleDragStart(e) {
    draggedTask = this;
    e.dataTransfer.setData('text/plain', this.dataset.taskId);
    dragTimeout = setTimeout(() => {
        this.style.opacity = '0.5';
    }, 0);
}

function handleDragEnd(e) {
    clearTimeout(dragTimeout);
    if (draggedTask) {
        draggedTask.style.opacity = '';
    }
    draggedTask = null;
    
    // Remover classes de hover
    document.querySelectorAll('.task-list.drag-over').forEach(col => {
        col.classList.remove('drag-over');
    });
}

function handleDragOver(e) {
    e.preventDefault();
    this.classList.add('drag-over');
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    e.preventDefault();
    this.classList.remove('drag-over');
    
    const taskId = e.dataTransfer.getData('text/plain');
    const novaColunaId = this.dataset.columnId;
    const taskCard = document.querySelector(`.task-card[data-task-id="${taskId}"]`);
    
    if (!taskCard || !novaColunaId) return;
    
    // Calcular nova ordem
    const tarefasNaColuna = Array.from(this.children);
    let novaOrdem = tarefasNaColuna.length;
    
    // Animar movimento
    taskCard.style.transition = 'all 0.3s';
    taskCard.style.opacity = '0.5';
    
    // Mover via AJAX
    const formData = new FormData();
    formData.append('acao', 'mover');
    formData.append('id', taskId);
    formData.append('coluna_id', novaColunaId);
    formData.append('ordem', novaOrdem);
    
    fetch('api_tarefas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remover da coluna antiga e adicionar na nova
            const oldColumn = taskCard.parentElement;
            taskCard.remove();
            this.appendChild(taskCard);
            taskCard.style.opacity = '';
            
            // Reordenar ambas as colunas
            reordenarTarefas(oldColumn);
            reordenarTarefas(this);
            atualizarContagemTarefas();
            mostrarNotificacao(data.message, 'success');
        } else {
            taskCard.style.opacity = '';
            mostrarNotificacao(data.message, 'error');
        }
    })
    .catch(error => {
        taskCard.style.opacity = '';
        mostrarNotificacao('Erro ao mover tarefa', 'error');
    });
}

function reordenarTarefas(column) {
    const tarefas = Array.from(column.querySelectorAll('.task-card'));
    const updates = tarefas.map((tarefa, index) => {
        return { id: tarefa.dataset.taskId, ordem: index };
    });
    
    if (updates.length === 0) return;
    
    const formData = new FormData();
    formData.append('acao', 'reordenar');
    formData.append('tarefas', JSON.stringify(updates));
    
    fetch('api_tarefas.php', {
        method: 'POST',
        body: formData
    }).catch(error => console.error('Erro ao reordenar:', error));
}

// ========== UTILITÁRIOS ==========
function atualizarContagemTarefas() {
    document.querySelectorAll('.column').forEach(column => {
        const taskList = column.querySelector('.task-list');
        const taskCount = column.querySelector('.task-count');
        if (taskCount) {
            const total = taskList.querySelectorAll('.task-card').length;
            taskCount.innerText = total;
        }
    });
}

function atualizarBoard(colunas) {
    colunas.forEach(coluna => {
        const columnElement = document.querySelector(`.column[data-column-id="${coluna.id}"]`);
        if (!columnElement) return;
        
        const taskList = columnElement.querySelector('.task-list');
        if (!taskList) return;
        
        // Salvar o HTML atual para comparar
        const currentTasks = Array.from(taskList.querySelectorAll('.task-card')).map(t => t.dataset.taskId);
        const newTasks = coluna.tarefas.map(t => t.id.toString());
        
        // Se mudou, atualizar
        if (JSON.stringify(currentTasks) !== JSON.stringify(newTasks)) {
            taskList.innerHTML = '';
            coluna.tarefas.forEach(tarefa => {
                taskList.appendChild(criarCardTarefa(tarefa));
            });
        }
    });
    
    // Reinicializar eventos
    inicializarDragAndDrop();
    reinicializarBotoesAcao();
}

function criarCardTarefa(tarefa) {
    const card = document.createElement('div');
    card.className = 'task-card';
    card.setAttribute('data-task-id', tarefa.id);
    card.setAttribute('draggable', true);
    
    card.innerHTML = `
        <div class="task-priority priority-${tarefa.prioridade}"></div>
        <h3>${escapeHtml(tarefa.titulo)}</h3>
        <p>${escapeHtml(tarefa.descricao?.substring(0, 80) || '')}</p>
        <div class="task-meta">
            ${tarefa.prazo ? `<span class="task-date">📅 ${formatarData(tarefa.prazo)}</span>` : ''}
            ${tarefa.responsavel_nome ? `<span class="task-assignee">👤 ${escapeHtml(tarefa.responsavel_nome)}</span>` : ''}
        </div>
        <div class="task-actions">
            <button class="btn-edit-task" data-task='${JSON.stringify(tarefa).replace(/'/g, "&#39;")}'>✏️</button>
            <button class="btn-delete-task" data-task-id="${tarefa.id}">🗑️</button>
        </div>
    `;
    
    return card;
}

function reinicializarBotoesAcao() {
    // Botões de editar
    document.querySelectorAll('.btn-edit-task').forEach(btn => {
        btn.onclick = function() {
            const task = JSON.parse(this.dataset.task);
            document.getElementById('taskId').value = task.id;
            document.getElementById('titulo').value = task.titulo;
            document.getElementById('descricao').value = task.descricao;
            document.getElementById('prioridade').value = task.prioridade;
            document.getElementById('prazo').value = task.prazo;
            document.getElementById('responsavel_id').value = task.responsavel_id || '';
            document.getElementById('columnId').value = task.coluna_id;
            document.getElementById('modalTitle').innerText = '✏️ Editar Tarefa';
            document.getElementById('taskModal').style.display = 'block';
        }
    });
    
    // Botões de excluir
    document.querySelectorAll('.btn-delete-task').forEach(btn => {
        btn.onclick = function() {
            if (confirm('Tem certeza que deseja excluir esta tarefa?')) {
                excluirTarefaViaAjax(this.dataset.taskId, this);
            }
        }
    });
}

function mostrarNotificacao(mensagem, tipo) {
    // Criar elemento de notificação
    const notificacao = document.createElement('div');
    notificacao.className = `notification notification-${tipo}`;
    notificacao.innerHTML = `
        <span>${tipo === 'success' ? '✅' : '❌'}</span>
        <span>${mensagem}</span>
    `;
    notificacao.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${tipo === 'success' ? '#4caf50' : '#f44336'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        z-index: 9999;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    `;
    
    document.body.appendChild(notificacao);
    
    setTimeout(() => {
        notificacao.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notificacao.remove(), 300);
    }, 3000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatarData(data) {
    if (!data) return '';
    const d = new Date(data);
    return d.toLocaleDateString('pt-BR');
}

// CSS para animações
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .task-list.drag-over {
        background: rgba(201, 162, 107, 0.1);
        border-radius: 12px;
        min-height: 100px;
    }
    .task-card {
        transition: all 0.2s ease;
        cursor: grab;
    }
    .task-card:active {
        cursor: grabbing;
    }
`;
document.head.appendChild(style);