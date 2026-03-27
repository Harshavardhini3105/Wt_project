document.addEventListener('DOMContentLoaded', () => {
    
    const saveBtn = document.getElementById('saveSettingsBtn');

    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            // Change button text temporarily to show it saved
            const originalText = this.innerText;
            this.innerText = "✅ Saved!";
            
            // Temporarily use the darker green hover color to indicate a change
            this.style.backgroundColor = "var(--primary-green-hover)"; 
            
            // Revert back after 2 seconds
            setTimeout(() => {
                this.innerText = originalText;
                this.style.backgroundColor = "var(--primary-green)";
            }, 2000);
        });
    }
});
