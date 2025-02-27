document.addEventListener('DOMContentLoaded', function () {
    console.log('Comment script initialized');
    // 处理回复按钮点击
    document.querySelectorAll('.reply-button').forEach(button => {
        console.log('Found reply button:', button.dataset.commentId);
        button.addEventListener('click', function (e) {
            console.log('Reply button clicked:', this.dataset.commentId);
            e.preventDefault(); // 阻止按钮默认行为
            const commentId = this.dataset.commentId;
            const replyForm = document.querySelector(`#reply-form-${commentId}`);
            console.log('Reply form found:', replyForm !== null);

            // 隐藏所有其他回复表单
            document.querySelectorAll('.reply-form-container').forEach(form => {
                if (form.id !== `reply-form-${commentId}`) {
                    form.classList.add('d-none');
                }
            });

            // 切换当前回复表单的显示状态
            const formContainer = document.getElementById(`reply-form-${commentId}`);
            formContainer.classList.toggle('d-none');
            console.log('Reply form visibility toggled');

            // 如果表单显示，聚焦到输入框
            if (!formContainer.classList.contains('d-none')) {
                formContainer.querySelector('textarea').focus();
            }
        });
    });

    // 其他现有的评论相关代码...
}); 