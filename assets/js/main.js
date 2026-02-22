// 校园论坛 JavaScript

// 移动端菜单切换
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenu.style.display === 'block') {
        mobileMenu.style.display = 'none';
    } else {
        mobileMenu.style.display = 'block';
    }
}

// 密码显示/隐藏切换
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.parentElement.querySelector('.toggle-password i');
    
    if (input.type === 'password') {
        input.type = 'text';
        button.classList.remove('fa-eye');
        button.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        button.classList.remove('fa-eye-slash');
        button.classList.add('fa-eye');
    }
}

// 自动隐藏提示消息
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});

// 表单验证
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // 添加错误提示样式
                    field.style.borderColor = '#ef4444';
                } else {
                    field.classList.remove('error');
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
});

// 输入框焦点效果
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input, textarea, select');
    
    inputs.forEach(function(input) {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
});

// 确认对话框
document.addEventListener('DOMContentLoaded', function() {
    const confirmLinks = document.querySelectorAll('[data-confirm]');
    
    confirmLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});

// 字符计数器
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('textarea[maxlength]');
    
    textareas.forEach(function(textarea) {
        const maxLength = textarea.getAttribute('maxlength');
        const counter = document.createElement('div');
        counter.className = 'char-counter';
        counter.style.cssText = 'text-align: right; font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;';
        
        textarea.parentNode.insertBefore(counter, textarea.nextSibling);
        
        function updateCounter() {
            const currentLength = textarea.value.length;
            counter.textContent = currentLength + ' / ' + maxLength;
            
            if (currentLength > maxLength * 0.9) {
                counter.style.color = '#ef4444';
            } else {
                counter.style.color = '#9ca3af';
            }
        }
        
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });
});

// 平滑滚动
document.addEventListener('DOMContentLoaded', function() {
    const smoothLinks = document.querySelectorAll('a[href^="#"]');
    
    smoothLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// 图片懒加载
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(function(img) {
            imageObserver.observe(img);
        });
    } else {
        // 回退处理
        images.forEach(function(img) {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
});

// 返回顶部按钮
document.addEventListener('DOMContentLoaded', function() {
    const backToTop = document.createElement('button');
    backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTop.className = 'back-to-top';
    backToTop.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background-color: var(--primary-color);
        color: white;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
        z-index: 99;
    `;
    
    document.body.appendChild(backToTop);
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTop.style.opacity = '1';
            backToTop.style.visibility = 'visible';
        } else {
            backToTop.style.opacity = '0';
            backToTop.style.visibility = 'hidden';
        }
    });
    
    backToTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});

// 搜索功能增强
document.addEventListener('DOMContentLoaded', function() {
    const searchInputs = document.querySelectorAll('input[type="search"]');
    
    searchInputs.forEach(function(input) {
        let searchTimeout;
        
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            searchTimeout = setTimeout(function() {
                // 这里可以添加实时搜索逻辑
                console.log('搜索:', input.value);
            }, 500);
        });
    });
});

// 防止表单重复提交
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function() {
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            
            submitButtons.forEach(function(button) {
                button.disabled = true;
                
                // 保存原始文本
                if (!button.dataset.originalText) {
                    button.dataset.originalText = button.innerHTML;
                }
                
                // 显示加载状态
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 处理中...';
            });
        });
    });
});

// 通知下拉
document.addEventListener('DOMContentLoaded', function() {
    var trigger = document.getElementById('notificationTrigger');
    var dropdown = document.getElementById('notificationDropdown');
    var listEl = document.getElementById('notificationDropdownList');
    if (!trigger || !dropdown || !listEl) return;

    var loaded = false;
    trigger.addEventListener('click', function(e) {
        e.preventDefault();
        dropdown.classList.toggle('show');
        if (!loaded && dropdown.classList.contains('show')) {
            loaded = true;
            fetch('api/notifications/list.php?per_page=10', { credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    var items = res.notifications || [];
                    var types = { new_message: '新消息', new_follower: '新粉丝', system: '系统', post_reply: '评论回复' };
                    if (items.length === 0) {
                        listEl.innerHTML = '<p class="text-muted" style="padding:0.75rem;">暂无通知</p>';
                    } else {
                        listEl.innerHTML = items.map(function(n) {
                            var typeLabel = types[n.type] || n.type;
                            var link = '';
                            if (n.type === 'new_message' && n.data && n.data.conversation_id) {
                                link = ' <a href="messages.php?id=' + n.data.conversation_id + '">查看</a>';
                            }
                            if (n.type === 'new_follower' && n.data && n.data.user_id) {
                                link = ' <a href="user_profile.php?id=' + n.data.user_id + '">查看</a>';
                            }
                            return '<div class="notif-item ' + (n.is_read ? '' : 'unread') + '">' + typeLabel + link + ' <span style="font-size:0.75rem;color:#9ca3af">' + n.created_at + '</span></div>';
                        }).join('');
                    }
                })
                .catch(function() {
                    listEl.innerHTML = '<p class="text-muted" style="padding:0.75rem;">加载失败</p>';
                });
        }
    });
    document.addEventListener('click', function(e) {
        if (dropdown.classList.contains('show') && !dropdown.contains(e.target) && !trigger.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
});
