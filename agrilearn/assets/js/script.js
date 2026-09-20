// AgriLearn front-end protections for the module viewer.
// Note: these are deterrents, not absolute protection — a determined
// user can still capture content (e.g. with a phone camera). Real
// protection ultimately relies on trust + access control on the server.
document.addEventListener('contextmenu', function (e) {
  if (e.target.closest('.module-viewer')) e.preventDefault();
});

document.addEventListener('keydown', function (e) {
  if (!document.querySelector('.module-viewer')) return;
  // Block common save/print/devtools shortcuts while a module is open
  const blocked =
    (e.ctrlKey && ['s', 'p', 'u', 'c'].includes(e.key.toLowerCase())) ||
    e.key === 'PrintScreen' ||
    e.key === 'F12';
  if (blocked) e.preventDefault();
});
