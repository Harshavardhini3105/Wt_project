document.addEventListener('DOMContentLoaded', () => {
    
    const saveBtn = document.getElementById('saveSettingsBtn');

    if (saveBtn) {
        saveBtn.addEventListener('click', async function() {
            // Collect data
            const settingsData = {
                fullname: document.getElementById('fullname').value,
                school: document.getElementById('school').value,
                study_level: document.getElementById('study_level').value,
                goal: document.getElementById('goal').value,
                focus_session_mins: document.getElementById('focus_session_mins').value,
                short_break_mins: document.getElementById('short_break_mins').value,
                daily_limit_hours: document.getElementById('daily_limit_hours').value,
                study_hours: document.getElementById('study_hours').value,
                pref_time: document.getElementById('pref_time').value,
                email_notify: document.getElementById('email_notify').checked ? 1 : 0,
                dark_mode: document.getElementById('dark_mode').checked ? 1 : 0
            };

            // Change button text temporarily to show it's saving
            const originalText = this.innerText;
            this.innerText = "Saving...";

            try {
                const response = await fetch('update_settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(settingsData)
                });

                const result = await response.json();

                if (result.success) {
                    this.innerText = "✅ Saved!";
                    this.style.backgroundColor = "var(--primary-green-hover)"; 
                    
                    // Apply Dark Mode immediately if toggled
                    if (settingsData.dark_mode) {
                        document.body.classList.add('dark-mode');
                    } else {
                        document.body.classList.remove('dark-mode');
                    }
                } else {
                    this.innerText = "❌ Error!";
                    this.style.backgroundColor = "red";
                    console.error('Error saving settings:', result.error);
                }
            } catch (error) {
                this.innerText = "❌ Error!";
                this.style.backgroundColor = "red";
                console.error('Fetch error:', error);
            }
            
            // Revert back after 2 seconds
            setTimeout(() => {
                this.innerText = originalText;
                this.style.backgroundColor = "var(--primary-green)";
            }, 2000);
        });
    }
});
