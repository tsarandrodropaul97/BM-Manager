document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('categorie-form');
    const overlay = document.getElementById('ajax-overlay');

    if (!form || !overlay) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        overlay.classList.add('active');

        const data = new FormData(form);

        fetch(form.action, {
            method: form.method,
            body: data,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.json())
            .then(json => {
                overlay.classList.remove('active');

                if (json.success) {
                    // stocke le message dans sessionStorage
                    sessionStorage.setItem('flashSuccess', json.message || 'Action réussie');
                    // redirige vers la page cible
                    window.location.href = json.redirect;
                } else if (json.errors) {
                    displayErrors(form, json.errors);
                }
            })
            .catch(err => {
                overlay.classList.remove('active');
                alert('Une erreur est survenue.');
                console.error(err);
            });
    });

    // Affichage des erreurs
    function displayErrors(form, errors) {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        for (const [fieldName, fieldErrors] of Object.entries(errors)) {
            const field = form.querySelector('[name="categorie[' + fieldName + ']"]');
            if (!field) continue;

            field.classList.add('is-invalid');
            const div = document.createElement('div');
            div.classList.add('invalid-feedback');
            div.innerHTML = fieldErrors.join('<br>');
            field.parentNode.appendChild(div);
        }
    }
});
