// FAQ Accordion — toggle class "active" sur .faq-item (animation via CSS max-height)
function toggleFaq(button) {
    const item = button.closest('.faq-item');
    const isActive = item.classList.contains('active');

    // Ferme tous les items ouverts
    document.querySelectorAll('.faq-item.active').forEach(el => el.classList.remove('active'));

    // Ouvre celui cliqué s'il était fermé
    if (!isActive) {
        item.classList.add('active');
    }
}

// Gestion de l'envoi du formulaire de contact
function handleSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    const formMessage = document.getElementById('formMessage');

    formMessage.textContent = 'Envoi en cours…';
    formMessage.style.color = '#8e7cc3';

    fetch('api/send_email.php', {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json' },
    })
    .then(response => {
        if (!response.ok) throw new Error('Erreur lors de l\'envoi');
        return response.json();
    })
    .then(() => {
        formMessage.textContent = 'Votre message a été envoyé avec succès !';
        formMessage.style.color = '#28a745';
        form.reset();
    })
    .catch(() => {
        formMessage.textContent = 'Une erreur est survenue. Veuillez réessayer plus tard.';
        formMessage.style.color = '#dc3545';
    });

    return false;
}

function toggleNav() {
    document.getElementById('navLinks').classList.toggle('open');
    document.getElementById('hamburger').classList.toggle('open');
}
function closeNav() {
    document.getElementById('navLinks').classList.remove('open');
    document.getElementById('hamburger').classList.remove('open');
}