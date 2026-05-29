const form = document.getElementById('loginForm');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const togglePwd = document.getElementById('togglePwd');
const errorMsg = document.getElementById('errorMsg');

// Afficher / cacher le mot de passe
togglePwd.addEventListener('click', () => {
  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    togglePwd.textContent = '🙈';
  } else {
    passwordInput.type = 'password';
    togglePwd.textContent = '👁';
  }
});

// Soumission du formulaire
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  errorMsg.classList.add('hidden');

  const email    = emailInput.value.trim();
  const password = passwordInput.value;

  try {
    const response = await fetch('/smartcampus/projet-Web-2026-ING2-gr14/api/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });

    const data = await response.json();

    if (data.success) {
      if (data.role === 'admin') window.location.href = '/smartcampus/projet-Web-2026-ING2-gr14/admin/dashboard-admin.php';
      if (data.role === 'prof')  window.location.href = '/smartcampus/projet-Web-2026-ING2-gr14/prof/dashboard-prof.php';
      if (data.role === 'eleve') window.location.href = '/smartcampus/projet-Web-2026-ING2-gr14/eleve/dashboard-eleve.php';
    } else {
      errorMsg.textContent = data.message || 'Email ou mot de passe incorrect.';
      errorMsg.classList.remove('hidden');
    }

  } catch (err) {
    errorMsg.textContent = 'Erreur de connexion au serveur.';
    errorMsg.classList.remove('hidden');
  }
});