import './bootstrap';
import '../css/app.css';

// 简单的交互增强
document.addEventListener('DOMContentLoaded', function() {
    // 自动关闭提示
    document.querySelectorAll('[data-auto-dismiss]').forEach(alertEl => {
        const timeout = Number(alertEl.dataset.autoDismiss) || 3000;
        setTimeout(() => {
            alertEl.classList.add('opacity-0');
            setTimeout(() => alertEl.remove(), 200);
        }, timeout);
    });

    const createToast = (message, variant = 'error') => {
        const toast = document.createElement('div');
        const baseClasses = 'pointer-events-auto flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg text-sm';
        const variantClasses = variant === 'error'
            ? 'bg-red-50 border-red-200 text-red-700'
            : 'bg-emerald-50 border-emerald-200 text-emerald-700';

        toast.className = `${baseClasses} ${variantClasses}`;
        toast.innerHTML = `
            <div class="flex-1 leading-relaxed">${message}</div>
            <button type="button" class="ml-2 text-xs text-neutral-500 hover:text-neutral-700">关闭</button>
        `;

        const containerId = 'global-toast-container';
        let container = document.getElementById(containerId);
        if (!container) {
            container = document.createElement('div');
            container.id = containerId;
            container.className = 'fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-xs';
            document.body.appendChild(container);
        }

        container.appendChild(toast);

        const removeToast = () => {
            toast.classList.add('opacity-0', 'translate-y-1');
            setTimeout(() => toast.remove(), 150);
        };

        toast.querySelector('button')?.addEventListener('click', removeToast);
        setTimeout(removeToast, 2800);
    };

    // 表单验证提示
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                createToast('请填写所有必填字段');
            }
        });
    });
    
    // 删除确认
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = button.closest('form');
            if (!form) return;

            const existing = document.getElementById('confirm-delete-dialog');
            if (existing) existing.remove();

            const overlay = document.createElement('div');
            overlay.id = 'confirm-delete-dialog';
            overlay.className = 'fixed inset-0 z-40 flex items-center justify-center bg-black/40 px-4';
            overlay.innerHTML = `
                <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
                    <h3 class="text-base font-semibold text-neutral-800 mb-2">确认删除</h3>
                    <p class="text-sm text-neutral-600 mb-6">删除后将无法恢复，确定要继续吗？</p>
                    <div class="flex justify-end gap-3">
                        <button type="button" data-action="cancel" class="btn-secondary text-sm">取消</button>
                        <button type="button" data-action="confirm" class="btn-primary text-sm">确认删除</button>
                    </div>
                </div>
            `;

            const closeDialog = () => overlay.remove();
            overlay.addEventListener('click', event => {
                if (event.target === overlay) closeDialog();
            });

            overlay.querySelector('[data-action="cancel"]')?.addEventListener('click', closeDialog);
            overlay.querySelector('[data-action="confirm"]')?.addEventListener('click', () => {
                closeDialog();
                form.submit();
            });

            document.body.appendChild(overlay);
        });
    });

    // 分类/搜索/分页无刷新
    const filterForm = document.querySelector('[data-topic-filter]');
    if (filterForm) {
        const listEl = document.querySelector('[data-topic-list]');
        const paginationEl = document.querySelector('[data-topic-pagination]');
        const categorySelect = filterForm.querySelector('select[name="category"]');
        const circleTypeSelect = filterForm.querySelector('select[name="circle_type"]');
        const searchInput = filterForm.querySelector('input[name="search"]');

        const buildUrl = () => {
            const formData = new FormData(filterForm);
            const params = new URLSearchParams();
            formData.forEach((value, key) => {
                if (value !== null && `${value}`.trim() !== '') {
                    params.set(key, value);
                }
            });
            const query = params.toString();
            return query ? `${filterForm.action}?${query}` : filterForm.action;
        };

        const updateList = async (url, replaceHistory = true) => {
            if (!listEl || !paginationEl) return;
            try {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) {
                    createToast('加载失败，请稍后再试');
                    return;
                }
                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const newList = doc.querySelector('[data-topic-list]');
                const newPagination = doc.querySelector('[data-topic-pagination]');
                if (newList) listEl.innerHTML = newList.innerHTML;
                if (newPagination) paginationEl.innerHTML = newPagination.innerHTML;
                if (replaceHistory) {
                    window.history.replaceState({}, '', url);
                }
            } catch (error) {
                createToast('加载失败，请稍后再试');
            }
        };

        if (categorySelect) {
            categorySelect.addEventListener('change', () => {
                updateList(buildUrl());
            });
        }

        if (circleTypeSelect) {
            circleTypeSelect.addEventListener('change', () => {
                updateList(buildUrl());
            });
        }

        filterForm.addEventListener('submit', event => {
            event.preventDefault();
            if (searchInput) searchInput.blur();
            updateList(buildUrl());
        });

        if (paginationEl) {
            paginationEl.addEventListener('click', event => {
                const link = event.target.closest('a[href]');
                if (!link) return;
                event.preventDefault();
                updateList(link.href);
            });
        }
    }

    // 用户下拉菜单
    const userDropdown = document.querySelector('[data-user-dropdown]');
    if (userDropdown) {
        const trigger = userDropdown.querySelector('[data-dropdown-trigger]');
        const menu = userDropdown.querySelector('[data-dropdown-menu]');

        trigger?.addEventListener('click', (e) => {
            e.stopPropagation();
            menu?.classList.toggle('hidden');
        });

        document.addEventListener('click', () => {
            menu?.classList.add('hidden');
        });

        menu?.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    // 动态扩展字段
    const extraFieldsContainer = document.querySelector('[data-extra-fields]');
    const categorySelectForExtra = document.querySelector('select[name="category"]');
    if (extraFieldsContainer && categorySelectForExtra) {
        const fieldLabels = {
            unit_number: '单元号',
            description: '描述',
            contact_name: '联系人',
            contact_phone: '联系电话',
            status: '状态',
            reported_at: '上报时间',
            assigned_to: '分配给',
            resolved_at: '解决时间',
            cost: '费用',
            title: '标题',
            involved_parties: '涉及方',
            unit_numbers: '涉及单元',
            contact_info: '联系方式',
            mediator: '调解人',
            resolution: '解决方案',
            fee_type: '费用类型',
            amount: '金额',
            due_date: '截止日期',
            payment_method: '支付方式',
            paid_at: '支付时间',
            receipt_number: '收据编号',
            late_fee: '滞纳金',
            total_amount: '总金额',
        };

        const categoryFields = {
            maintenance: ['unit_number', 'description', 'contact_name', 'contact_phone', 'status', 'reported_at', 'assigned_to', 'resolved_at', 'cost'],
            conflict: ['title', 'description', 'status', 'reported_at', 'involved_parties', 'unit_numbers', 'contact_info', 'mediator', 'resolution', 'resolved_at'],
            fee: ['fee_type', 'amount', 'due_date', 'status', 'unit_number', 'payment_method', 'paid_at', 'receipt_number', 'late_fee', 'total_amount'],
        };

        const existingValues = window.extraFieldValues || {};

        const renderExtraFields = (category) => {
            const fields = categoryFields[category] || [];
            if (fields.length === 0) {
                extraFieldsContainer.innerHTML = '';
                return;
            }

            let html = '';
            fields.forEach(field => {
                const label = fieldLabels[field] || field;
                const value = existingValues[field] || '';
                const inputType = field.includes('at') || field.includes('date') ? 'date' : 'text';
                const isTextarea = field === 'description' || field === 'resolution' || field === 'involved_parties';

                html += `
                    <div class="mb-4">
                        <label for="extra_fields_${field}" class="block text-gray-700 text-sm font-medium mb-2">${label}</label>
                        ${isTextarea ? `
                            <textarea id="extra_fields_${field}" name="extra_fields[${field}]" rows="3"
                                      class="input-field">${value}</textarea>
                        ` : `
                            <input type="${inputType}" id="extra_fields_${field}" name="extra_fields[${field}]" value="${value}"
                                   class="input-field">
                        `}
                    </div>
                `;
            });

            extraFieldsContainer.innerHTML = html;
        };

        categorySelectForExtra.addEventListener('change', () => {
            renderExtraFields(categorySelectForExtra.value);
        });

        renderExtraFields(categorySelectForExtra.value);
    }
});
